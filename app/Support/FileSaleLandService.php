<?php

namespace App\Support;

use App\Models\DayBookEntry;
use App\Models\FileSaleCollective;
use App\Models\FileSaleLand;
use App\Models\Project;
use App\Models\ProjectSaleExemptionSnapshot;
use App\Models\PurchaseFile;
use App\Models\Sale;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class FileSaleLandService
{
    public const PLACEMENT_SEPARATE = 'separate';

    public const PLACEMENT_NEW_COLLECTIVE = 'new_collective';

    public const PLACEMENT_EXISTING_COLLECTIVE = 'existing_collective';

    /**
     * @return list<int>
     */
    public function movedSaleLandIds(Project $project): array
    {
        return FileSaleLand::query()
            ->where('project_id', $project->id)
            ->pluck('sale_land_id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    /**
     * @return Collection<int, FileSaleCollective>
     */
    public function openCollectives(Project $project): Collection
    {
        return FileSaleCollective::query()
            ->where('project_id', $project->id)
            ->where('status', FileSaleCollective::STATUS_OPEN)
            ->withCount('fileSaleLands')
            ->orderBy('id')
            ->get();
    }

    /**
     * @return Collection<int, FileSaleCollective>
     */
    public function collectivesForProject(Project $project): Collection
    {
        return FileSaleCollective::query()
            ->where('project_id', $project->id)
            ->withCount('fileSaleLands')
            ->with(['fileSaleLands.saleLand.purchaseItems'])
            ->orderBy('id')
            ->get();
    }

    public function nextCollectiveName(Project $project): string
    {
        $existing = FileSaleCollective::query()
            ->where('project_id', $project->id)
            ->pluck('name');

        $max = 0;
        foreach ($existing as $name) {
            if (preg_match('/^Collective-(\d+)$/i', (string) $name, $m)) {
                $max = max($max, (int) $m[1]);
            }
        }

        return 'Collective-'.($max + 1);
    }

    /**
     * @return array{marla_per_acre: float, components: list<array<string, mixed>>}
     */
    public function currentExemptionPayload(Project $project): array
    {
        ProjectExemptionDefaults::ensureForProject($project);
        $project->loadMissing([
            'saleExemptionComponents' => fn ($q) => $q->orderBy('sort_order'),
            'saleExemptionComponents.plotTypes' => fn ($q) => $q->orderBy('sort_order'),
        ]);

        return ProjectSaleExemptionSnapshot::payloadFromProject($project);
    }

    /**
     * @param  list<int>  $purchaseFileIds
     * @return array{
     *   moved: list<int>,
     *   skipped: list<int>,
     *   moved_ids: list<int>,
     *   collective: array{id: int, name: string, status: string}|null
     * }
     */
    public function moveToFileSale(
        Project $project,
        array $purchaseFileIds,
        string $placement = self::PLACEMENT_SEPARATE,
        ?int $collectiveId = null,
        ?string $collectiveName = null,
    ): array {
        $purchaseFileIds = collect($purchaseFileIds)
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        if ($purchaseFileIds === []) {
            throw ValidationException::withMessages([
                'purchase_file_ids' => ['Select at least one sale land file to move.'],
            ]);
        }

        if (! in_array($placement, [
            self::PLACEMENT_SEPARATE,
            self::PLACEMENT_NEW_COLLECTIVE,
            self::PLACEMENT_EXISTING_COLLECTIVE,
        ], true)) {
            throw ValidationException::withMessages([
                'placement' => ['Choose Separate, New collective, or Existing collective.'],
            ]);
        }

        $files = PurchaseFile::query()
            ->where('project_id', $project->id)
            ->whereIn('id', $purchaseFileIds)
            ->whereNotNull('sale_land_at')
            ->with('purchaseItems')
            ->get()
            ->keyBy('id');

        $invalidIds = array_values(array_diff($purchaseFileIds, $files->keys()->all()));
        if ($invalidIds !== []) {
            throw ValidationException::withMessages([
                'purchase_file_ids' => ['One or more selected files are not valid sale land records for this project.'],
            ]);
        }

        $alreadyMoved = FileSaleLand::query()
            ->where('project_id', $project->id)
            ->whereIn('sale_land_id', $purchaseFileIds)
            ->pluck('sale_land_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $toMove = array_values(array_diff($purchaseFileIds, $alreadyMoved));
        $skipped = $alreadyMoved;

        if ($toMove === []) {
            return [
                'moved' => [],
                'skipped' => $skipped,
                'moved_ids' => $this->movedSaleLandIds($project),
                'collective' => null,
            ];
        }

        $collective = null;

        $moved = DB::transaction(function () use (
            $project,
            $toMove,
            $files,
            $placement,
            $collectiveId,
            $collectiveName,
            &$collective
        ) {
            if ($placement === self::PLACEMENT_NEW_COLLECTIVE) {
                $collective = $this->createCollectiveHeader(
                    $project,
                    $collectiveName ?: $this->nextCollectiveName($project),
                    collect($toMove)->map(fn (int $id) => $files->get($id))->filter()
                );
            } elseif ($placement === self::PLACEMENT_EXISTING_COLLECTIVE) {
                $collective = $this->resolveOpenCollective($project, $collectiveId);
            }

            $movedIds = [];
            foreach ($toMove as $purchaseFileId) {
                FileSaleLand::create([
                    'project_id' => $project->id,
                    'sale_land_id' => $purchaseFileId,
                    'collective_id' => $collective?->id,
                ]);
                $movedIds[] = $purchaseFileId;
            }

            if ($collective) {
                $this->refreshCollectiveTotals($collective);
            }

            return $movedIds;
        });

        return [
            'moved' => $moved,
            'skipped' => $skipped,
            'moved_ids' => $this->movedSaleLandIds($project),
            'collective' => $collective ? [
                'id' => (int) $collective->id,
                'name' => $collective->name,
                'status' => $collective->status,
            ] : null,
        ];
    }

    /**
     * Save already-moved separate files as a named sale file (header + lines + exemption formula).
     *
     * @param  list<int>  $saleLandIds
     * @return array{collective: array{id: int, name: string, status: string}, file_count: int}
     */
    public function saveAsSaleFile(Project $project, array $saleLandIds, string $name): array
    {
        $name = trim($name);
        if ($name === '') {
            throw ValidationException::withMessages([
                'name' => ['Enter a sale file name.'],
            ]);
        }

        $saleLandIds = collect($saleLandIds)
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        if ($saleLandIds === []) {
            throw ValidationException::withMessages([
                'sale_land_ids' => ['Select at least one file to save as a sale file.'],
            ]);
        }

        $rows = FileSaleLand::query()
            ->where('project_id', $project->id)
            ->whereIn('sale_land_id', $saleLandIds)
            ->with('saleLand.purchaseItems')
            ->get();

        if ($rows->count() !== count($saleLandIds)) {
            throw ValidationException::withMessages([
                'sale_land_ids' => ['One or more selected files are not in file sale for this project.'],
            ]);
        }

        $alreadyGrouped = $rows->filter(fn (FileSaleLand $row) => $row->collective_id !== null);
        if ($alreadyGrouped->isNotEmpty()) {
            throw ValidationException::withMessages([
                'sale_land_ids' => ['Only separate files (not already in a saved sale file) can be saved here.'],
            ]);
        }

        if (FileSaleCollective::query()
            ->where('project_id', $project->id)
            ->where('name', $name)
            ->exists()) {
            throw ValidationException::withMessages([
                'name' => ['A sale file with this name already exists for this project.'],
            ]);
        }

        $memberFiles = $rows->map(fn (FileSaleLand $row) => $row->saleLand)->filter();

        $collective = DB::transaction(function () use ($project, $rows, $name, $memberFiles) {
            $collective = $this->createCollectiveHeader($project, $name, $memberFiles);

            FileSaleLand::query()
                ->whereIn('id', $rows->pluck('id')->all())
                ->update(['collective_id' => $collective->id]);

            $this->refreshCollectiveTotals($collective);

            return $collective->fresh();
        });

        return [
            'collective' => [
                'id' => (int) $collective->id,
                'name' => $collective->name,
                'status' => $collective->status,
            ],
            'file_count' => $rows->count(),
        ];
    }

    /**
     * @param  list<int>  $saleLandIds
     * @return array{collective: array{id: int, name: string, status: string}, file_count: int}
     */
    public function groupIntoCollective(
        Project $project,
        array $saleLandIds,
        string $placement = self::PLACEMENT_NEW_COLLECTIVE,
        ?int $collectiveId = null,
        ?string $collectiveName = null,
    ): array {
        if ($placement === self::PLACEMENT_NEW_COLLECTIVE) {
            return $this->saveAsSaleFile(
                $project,
                $saleLandIds,
                $collectiveName ?: $this->nextCollectiveName($project)
            );
        }

        $saleLandIds = collect($saleLandIds)
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        if ($saleLandIds === []) {
            throw ValidationException::withMessages([
                'sale_land_ids' => ['Select at least one separate file to group.'],
            ]);
        }

        $rows = FileSaleLand::query()
            ->where('project_id', $project->id)
            ->whereIn('sale_land_id', $saleLandIds)
            ->get();

        if ($rows->count() !== count($saleLandIds)) {
            throw ValidationException::withMessages([
                'sale_land_ids' => ['One or more selected files are not in file sale for this project.'],
            ]);
        }

        $alreadyGrouped = $rows->filter(fn (FileSaleLand $row) => $row->collective_id !== null);
        if ($alreadyGrouped->isNotEmpty()) {
            throw ValidationException::withMessages([
                'sale_land_ids' => ['Only separate files (not already in a sale file) can be grouped.'],
            ]);
        }

        $collective = DB::transaction(function () use ($project, $rows, $collectiveId) {
            $collective = $this->resolveOpenCollective($project, $collectiveId);

            FileSaleLand::query()
                ->whereIn('id', $rows->pluck('id')->all())
                ->update(['collective_id' => $collective->id]);

            $this->refreshCollectiveTotals($collective);

            return $collective->fresh();
        });

        return [
            'collective' => [
                'id' => (int) $collective->id,
                'name' => $collective->name,
                'status' => $collective->status,
            ],
            'file_count' => $rows->count(),
        ];
    }

    public function completeCollective(Project $project, FileSaleCollective $collective): FileSaleCollective
    {
        if ((int) $collective->project_id !== (int) $project->id) {
            throw ValidationException::withMessages([
                'collective' => ['Sale file does not belong to this project.'],
            ]);
        }

        if ($collective->isCompleted()) {
            return $collective;
        }

        $collective->update([
            'status' => FileSaleCollective::STATUS_COMPLETED,
            'completed_at' => now(),
        ]);

        return $collective->fresh();
    }

    public function reopenCollective(Project $project, FileSaleCollective $collective): FileSaleCollective
    {
        if ((int) $collective->project_id !== (int) $project->id) {
            throw ValidationException::withMessages([
                'collective' => ['Sale file does not belong to this project.'],
            ]);
        }

        $collective->update([
            'status' => FileSaleCollective::STATUS_OPEN,
            'completed_at' => null,
        ]);

        return $collective->fresh();
    }

    /**
     * Re-apply an exemption formula to a saved sale file (works even when completed).
     * Pass a saved snapshot id, or null to use the current project exemption setup.
     */
    public function applyExemptionToCollective(
        Project $project,
        FileSaleCollective $collective,
        ?int $snapshotId = null,
    ): FileSaleCollective {
        if ((int) $collective->project_id !== (int) $project->id) {
            throw ValidationException::withMessages([
                'collective' => ['Sale file does not belong to this project.'],
            ]);
        }

        if ($snapshotId !== null) {
            $snapshot = ProjectSaleExemptionSnapshot::query()
                ->where('project_id', $project->id)
                ->whereKey($snapshotId)
                ->first();

            if (! $snapshot) {
                throw ValidationException::withMessages([
                    'snapshot_id' => ['Selected exemption was not found for this project.'],
                ]);
            }

            $payload = $snapshot->payload;
        } else {
            $payload = $this->currentExemptionPayload($project);
        }

        $collective->update(['exemption_payload' => $payload]);

        return $collective->fresh();
    }

    /**
     * @param  Collection<int, PurchaseFile|null>  $memberFiles
     */
    private function createCollectiveHeader(Project $project, string $name, Collection $memberFiles): FileSaleCollective
    {
        $name = trim($name);
        if ($name === '') {
            $name = $this->nextCollectiveName($project);
        }

        if (FileSaleCollective::query()
            ->where('project_id', $project->id)
            ->where('name', $name)
            ->exists()) {
            throw ValidationException::withMessages([
                'name' => ['A sale file with this name already exists for this project.'],
            ]);
        }

        $totalMarla = round((float) $memberFiles->sum(
            fn ($file) => $file ? (float) $file->purchaseItems->sum('land_area_marla') : 0.0
        ), 4);

        return FileSaleCollective::create([
            'project_id' => $project->id,
            'name' => $name,
            'status' => FileSaleCollective::STATUS_OPEN,
            'exemption_payload' => $this->currentExemptionPayload($project),
            'total_land_marla' => $totalMarla,
        ]);
    }

    private function refreshCollectiveTotals(FileSaleCollective $collective): void
    {
        $collective->load(['fileSaleLands.saleLand.purchaseItems']);
        $totalMarla = round((float) $collective->fileSaleLands->sum(
            fn (FileSaleLand $row) => (float) ($row->saleLand?->purchaseItems->sum('land_area_marla') ?? 0)
        ), 4);

        $collective->update(['total_land_marla' => $totalMarla]);
    }

    private function resolveOpenCollective(Project $project, ?int $collectiveId): FileSaleCollective
    {
        if ($collectiveId === null || $collectiveId < 1) {
            throw ValidationException::withMessages([
                'collective_id' => ['Select an open sale file.'],
            ]);
        }

        $collective = FileSaleCollective::query()
            ->where('project_id', $project->id)
            ->where('id', $collectiveId)
            ->first();

        if (! $collective) {
            throw ValidationException::withMessages([
                'collective_id' => ['Selected sale file was not found.'],
            ]);
        }

        if (! $collective->isOpen()) {
            throw ValidationException::withMessages([
                'collective_id' => ['Completed sale files cannot accept more files. Reopen first, or choose another.'],
            ]);
        }

        return $collective;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function buildCollectivesSummary(Project $project, Collection $movedFiles): array
    {
        $links = FileSaleLand::query()
            ->where('project_id', $project->id)
            ->whereNotNull('collective_id')
            ->get()
            ->groupBy('collective_id');

        $filesById = $movedFiles->keyBy('id');
        $config = SaleExemptionConfig::forProject($project);

        return $this->collectivesForProject($project)->map(function (FileSaleCollective $collective) use ($project, $links, $filesById, $config) {
            $memberIds = ($links->get($collective->id) ?? collect())
                ->pluck('sale_land_id')
                ->map(fn ($id) => (int) $id)
                ->all();

            $members = collect($memberIds)
                ->map(fn (int $id) => $filesById->get($id))
                ->filter()
                ->values();

            $totalMarla = (float) ($collective->total_land_marla > 0
                ? $collective->total_land_marla
                : $members->sum(fn (PurchaseFile $file) => (float) $file->purchaseItems->sum('land_area_marla')));

            $exemptionPayload = $collective->exemption_payload
                ?: $this->currentExemptionPayload($project);
            $exemptionConfig = SaleExemptionConfig::fromSnapshotData($project, $exemptionPayload);

            $memberIds = $members->map(fn (PurchaseFile $file) => (int) $file->id)->all();
            $sales = $memberIds === []
                ? collect()
                : Sale::query()
                    ->where('project_id', $project->id)
                    ->where('sale_type', Sale::TYPE_SALE_LAND)
                    ->whereIn('purchase_file_id', $memberIds)
                    ->orderBy('id')
                    ->get();

            $soldMarla = round((float) $members->sum(fn (PurchaseFile $file) => $file->soldLandMarla()), 6);
            $remainingMarla = max(0.0, round($totalMarla - $soldMarla, 6));

            $formulaColumns = [];
            $balanceRows = [];
            if ($totalMarla > 0) {
                $calc = SaleExemptionFileCalculator::calculate($totalMarla, $exemptionConfig);
                foreach ($calc['rows'] as $calcRow) {
                    $available = (float) ($calcRow['file_count'] ?? 0);
                    $componentSlug = (string) ($calcRow['component_slug'] ?? '');
                    $plotSlug = (string) ($calcRow['plot_slug'] ?? '');
                    $soldParts = $sales
                        ->where('component', $componentSlug)
                        ->where('plot_type', $plotSlug)
                        ->map(fn (Sale $sale) => (int) $sale->plot_quantity)
                        ->filter(fn (int $qty) => $qty > 0)
                        ->values()
                        ->all();
                    $soldQty = (int) array_sum($soldParts);
                    $fileBal = round($available - $soldQty, 4);
                    $balSteps = [];
                    $running = $available;
                    foreach ($soldParts as $part) {
                        $running = round($running - $part, 4);
                        $balSteps[] = $running;
                    }

                    $column = [
                        'column_code' => str_replace('.', '', $calcRow['code']),
                        'short_label' => $this->shortPlotLabel($calcRow['plot_label']),
                        'file_count' => SaleExemptionFileCalculator::formatFileCount($available),
                        'component_slug' => $componentSlug,
                        'component_label' => (string) ($calcRow['component_label'] ?? ''),
                        'plot_slug' => $plotSlug,
                        'available' => round($available, 4),
                        'sold' => $soldQty,
                        'sold_parts' => $soldParts,
                        'sold_display' => $this->formatSoldPaymentDisplay($soldParts, $soldQty),
                        'file_bal' => $fileBal,
                        'file_bal_display' => $this->formatFileBalDisplay($fileBal, $balSteps),
                        'is_complete' => $soldQty > 0 && abs($fileBal) < 1e-6,
                    ];
                    $formulaColumns[] = $column;
                    $balanceRows[] = $column;
                }
            }

            $leftoverBalance = $this->buildLeftoverBalance(
                $project,
                $memberIds,
                $exemptionConfig,
                $members,
            );

            return [
                'id' => (int) $collective->id,
                'name' => $collective->name,
                'status' => $collective->status,
                'is_open' => $collective->isOpen(),
                'file_count' => $members->count(),
                'files' => $members->map(fn (PurchaseFile $file) => [
                    'id' => (int) $file->id,
                    'name' => $file->file_name,
                ])->all(),
                'total_land_area' => $totalMarla > 0
                    ? LandMeasure::formatAkmsLabelFromMarla($totalMarla)
                    : '—',
                'total_land_sheet' => $totalMarla > 0
                    ? $this->formatAkmsSheetFromMarla($totalMarla)
                    : '—',
                'total_land_marla' => round($totalMarla, 4),
                'sold_land_area' => $soldMarla > 0
                    ? LandMeasure::formatAkmsLabelFromMarla($soldMarla)
                    : '—',
                'remaining_land_area' => $totalMarla > 0
                    ? LandMeasure::formatAkmsLabelFromMarla($remainingMarla)
                    : '—',
                'remaining_land_sheet' => $totalMarla > 0
                    ? $this->formatAkmsSheetFromMarla($remainingMarla)
                    : '—',
                'remaining_land_marla' => $remainingMarla,
                'exemption_summary' => $collective->exemptionSummaryLabel(),
                'exemption_payload' => $exemptionPayload,
                'formula_columns' => $formulaColumns,
                'balance_rows' => $balanceRows,
                'leftover_balance' => $leftoverBalance,
                'marla_per_acre' => (float) ($exemptionPayload['marla_per_acre'] ?? $config->marlaPerAcreLand()),
            ];
        })->values()->all();
    }

    /**
     * @return list<array{id: int, name: string}>
     */
    public function separateMovedFiles(Project $project, Collection $movedFiles): array
    {
        $groupedIds = FileSaleLand::query()
            ->where('project_id', $project->id)
            ->whereNotNull('collective_id')
            ->pluck('sale_land_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        return $movedFiles
            ->reject(fn (PurchaseFile $file) => in_array((int) $file->id, $groupedIds, true))
            ->map(fn (PurchaseFile $file) => [
                'id' => (int) $file->id,
                'name' => $file->file_name,
            ])
            ->values()
            ->all();
    }

    /**
     * @return Collection<int, PurchaseFile>
     */
    public function movedSaleLandFiles(Project $project): Collection
    {
        $ids = $this->movedSaleLandIds($project);
        if ($ids === []) {
            return collect();
        }

        return PurchaseFile::query()
            ->where('project_id', $project->id)
            ->whereIn('id', $ids)
            ->whereNotNull('sale_land_at')
            ->with(['purchaseItems.party', 'fileSaleLand'])
            ->orderBy('file_name')
            ->get();
    }

    /**
     * @return array{
     *   rows: list<array{
     *     file_type: string,
     *     total_files: string,
     *     total_land_area: string,
     *     total_land_marla: float,
     *     file_calculation: string,
     *     file_calculation_breakdown: string
     *   }>,
     *   totals: array{
     *     total_land_area: string,
     *     total_land_marla: float,
     *     total_files: string,
     *     total_files_count: float,
     *     grand_total_amount: float,
     *     grand_total_amount_formatted: string,
     *     total_land_compact: string,
     *     sale_files_amount: float,
     *     sale_files_amount_formatted: string
     *   },
     *   files_land_columns: list<array{
     *     column_code: string,
     *     short_label: string,
     *     file_count: string
     *   }>,
     *   daybook_rows: list<array{
     *     category: string,
     *     sub_category: string,
     *     total_amount: float,
     *     total_amount_formatted: string
     *   }>,
     *   moved_files: list<array{
     *     id: int,
     *     name: string,
     *     owners: list<array<string, mixed>>,
     *     total_amount_formatted: string
     *   }>
     * }
     */
    public function buildFileSaleSummary(Project $project): array
    {
        ProjectExemptionDefaults::ensureForProject($project);

        $files = $this->movedSaleLandFiles($project);
        $config = SaleExemptionConfig::forProject($project);

        $totalMarla = 0.0;
        $grandTotalAmount = 0.0;

        foreach ($files as $file) {
            $totalMarla += (float) $file->purchaseItems->sum('land_area_marla');
            $grandTotalAmount += (float) $file->purchaseItems->sum('line_total_rs');
        }

        $rows = [];
        $filesLandColumns = [];
        $totalFilesCount = 0.0;
        $purchaseFileIds = $files->pluck('id')->all();

        if ($totalMarla > 0) {
            $calculator = SaleExemptionFileCalculator::calculate($totalMarla, $config);

            foreach ($calculator['rows'] as $calcRow) {
                $fileCount = (float) $calcRow['file_count'];
                $totalFilesCount += $fileCount;

                $filesLandColumns[] = [
                    'column_code' => str_replace('.', '', $calcRow['code']),
                    'short_label' => $this->shortPlotLabel($calcRow['plot_label']),
                    'file_count' => SaleExemptionFileCalculator::formatFileCount($fileCount),
                ];

                $rows[] = [
                    'column_code' => str_replace('.', '', $calcRow['code']),
                    'file_type' => $calcRow['plot_label'].' ('.$calcRow['component_label'].')',
                    'short_label' => $this->shortPlotLabel($calcRow['plot_label']),
                    'total_files' => SaleExemptionFileCalculator::formatFileCount($fileCount),
                    'total_land_area' => LandMeasure::formatAkmsLabelFromMarla((float) $calcRow['total_line_marla']),
                    'total_land_marla' => (float) $calcRow['total_line_marla'],
                    'file_calculation' => SaleExemptionFileCalculator::formatFileCount($fileCount),
                    'file_calculation_breakdown' => $this->formatFileCalculationBreakdown(
                        $calcRow['full_files'],
                        $calcRow['fraction_files'],
                        $calcRow['fraction_marla'],
                    ),
                ];
            }
        }

        $saleFilesAmount = $purchaseFileIds === []
            ? 0.0
            : (float) Sale::query()
                ->where('project_id', $project->id)
                ->whereIn('purchase_file_id', $purchaseFileIds)
                ->sum('total_amount');

        $files->loadMissing('sales');
        $soldMarla = round((float) $files->sum(fn (PurchaseFile $file) => $file->soldLandMarla()), 6);
        $remainingMarla = max(0.0, round($totalMarla - $soldMarla, 6));
        $leftoverBalance = $this->buildLeftoverBalance($project, $purchaseFileIds, $config, $files);

        return [
            'rows' => $rows,
            'files_land_columns' => $filesLandColumns,
            'totals' => [
                'total_land_area' => $totalMarla > 0 ? LandMeasure::formatAkmsLabelFromMarla($totalMarla) : '—',
                'total_land_compact' => $totalMarla > 0 ? LandMeasure::formatAkmsCompactFromMarla($totalMarla) : '—',
                'total_land_marla' => round($totalMarla, 4),
                'sold_land_marla' => $soldMarla,
                'sold_land_area' => $soldMarla > 0 ? LandMeasure::formatAkmsLabelFromMarla($soldMarla) : '—',
                'remaining_land_marla' => $remainingMarla,
                'remaining_land_area' => $totalMarla > 0 ? LandMeasure::formatAkmsLabelFromMarla($remainingMarla) : '—',
                'total_files' => $totalFilesCount > 0 ? SaleExemptionFileCalculator::formatFileCount($totalFilesCount) : '—',
                'total_files_count' => round($totalFilesCount, 4),
                'grand_total_amount' => round($grandTotalAmount, 2),
                'grand_total_amount_formatted' => 'Rs '.number_format($grandTotalAmount, 0),
                'sale_files_amount' => round($saleFilesAmount, 2),
                'sale_files_amount_formatted' => 'Rs '.number_format($saleFilesAmount, 0),
            ],
            'daybook_rows' => $this->buildDaybookSummary($project, $purchaseFileIds),
            'moved_files' => $files->map(fn (PurchaseFile $file) => array_merge(
                $file->daybookSaleFilePayload(),
                ['name' => $file->file_name]
            ))->values()->all(),
            'collectives' => $this->buildCollectivesSummary($project, $files),
            'separate_files' => $this->separateMovedFiles($project, $files),
            'open_collectives' => $this->openCollectives($project)->map(fn (FileSaleCollective $c) => [
                'id' => (int) $c->id,
                'name' => $c->name,
                'file_count' => (int) ($c->file_sale_lands_count ?? 0),
            ])->values()->all(),
            'area_balance' => $this->buildAreaBalanceByMoza($project, $purchaseFileIds, $config),
            'leftover_balance' => $leftoverBalance,
        ];
    }

    /**
     * Per-file leftover land + formula plot remaining after sale_land sales.
     *
     * @param  list<int>  $purchaseFileIds
     * @param  Collection<int, PurchaseFile>  $files
     * @return array{
     *   formula_columns: list<array<string, mixed>>,
     *   files: list<array<string, mixed>>,
     *   totals: array<string, mixed>
     * }
     */
    private function buildLeftoverBalance(
        Project $project,
        array $purchaseFileIds,
        SaleExemptionConfig $config,
        Collection $files
    ): array {
        if ($purchaseFileIds === []) {
            return [
                'formula_columns' => [],
                'files' => [],
                'totals' => [
                    'total_land' => '—',
                    'sold_land' => '—',
                    'remaining_land' => '—',
                    'files_count' => 0,
                    'total_khasras' => 0,
                    'mouzas_count' => 0,
                    'mouzas_names' => '—',
                    'partially_sold' => 0,
                    'fully_sold' => 0,
                    'formula_remaining' => [],
                ],
            ];
        }

        $sheet = SaleLandMozaGroups::spreadsheetForProject($project, $purchaseFileIds);
        $formulaColumns = collect($sheet['formula_columns'] ?? [])
            ->map(function (array $column) {
                return array_merge($column, [
                    'column_code' => str_replace('.', '', (string) ($column['code'] ?? '')),
                ]);
            })
            ->values()
            ->all();

        $sheetRowsByFile = collect($sheet['rows'] ?? [])->groupBy(
            fn (array $row) => (int) ($row['purchase_file_id'] ?? 0)
        );

        $usedSales = Sale::query()
            ->where('project_id', $project->id)
            ->where('sale_type', Sale::TYPE_SALE_LAND)
            ->whereIn('purchase_file_id', $purchaseFileIds)
            ->get()
            ->groupBy('purchase_file_id');

        $fileRows = [];
        $totalMarla = 0.0;
        $soldMarla = 0.0;
        $totalKhasras = 0;
        $allMozas = collect();
        $formulaRemainingTotals = [];
        $formulaSoldTotals = [];
        $formulaAvailableTotals = [];
        foreach ($formulaColumns as $column) {
            $plotKey = (string) ($column['plot_key'] ?? '');
            $formulaRemainingTotals[$plotKey] = 0.0;
            $formulaSoldTotals[$plotKey] = 0.0;
            $formulaAvailableTotals[$plotKey] = 0.0;
        }

        foreach ($files as $file) {
            $fileId = (int) $file->id;
            $fileSheetRows = $sheetRowsByFile->get($fileId, collect());
            $total = $file->totalLandMarla();
            $sold = $file->soldLandMarla();
            $remaining = $file->remainingLandMarla();
            $totalMarla += $total;
            $soldMarla += $sold;

            $plots = [];
            $fileUsed = $usedSales->get($fileId, collect());
            foreach ($formulaColumns as $column) {
                $plotKey = (string) ($column['plot_key'] ?? '');
                $available = (float) $fileSheetRows->sum(
                    fn (array $row) => (float) ($row['formula_values'][$plotKey]['file_count'] ?? 0)
                );
                if ($available <= 0 && $fileSheetRows->isEmpty() && $total > 0) {
                    // Fallback when sheet has no rows: derive from file total marla.
                    $calc = SaleExemptionFileCalculator::calculate($total, $config);
                    foreach ($calc['rows'] as $calcRow) {
                        if (
                            ($calcRow['component_slug'] ?? '') === ($column['component_slug'] ?? '')
                            && ($calcRow['plot_slug'] ?? '') === ($column['plot_slug'] ?? '')
                        ) {
                            $available = (float) ($calcRow['file_count'] ?? 0);
                            break;
                        }
                    }
                }

                $usedQty = (int) $fileUsed
                    ->where('component', $column['component_slug'] ?? '')
                    ->where('plot_type', $column['plot_slug'] ?? '')
                    ->sum('plot_quantity');
                $left = max(0.0, $available - $usedQty);

                $plots[$plotKey] = [
                    'available' => round($available, 4),
                    'available_display' => SaleExemptionFileCalculator::formatFileCount($available),
                    'sold' => $usedQty,
                    'sold_display' => $usedQty > 0 ? (string) $usedQty : '—',
                    'remaining' => round($left, 4),
                    'remaining_display' => SaleExemptionFileCalculator::formatFileCount($left),
                    'is_depleted' => $left <= 1e-6 && $available > 1e-6,
                ];

                $formulaAvailableTotals[$plotKey] = ($formulaAvailableTotals[$plotKey] ?? 0) + $available;
                $formulaSoldTotals[$plotKey] = ($formulaSoldTotals[$plotKey] ?? 0) + $usedQty;
                $formulaRemainingTotals[$plotKey] = ($formulaRemainingTotals[$plotKey] ?? 0) + $left;
            }

            $moza = $file->purchaseItems
                ->pluck('moza')
                ->filter(fn ($v) => filled($v))
                ->map(fn ($v) => trim((string) $v))
                ->unique()
                ->values();

            $totalKhasras += $file->purchaseItems->count();
            foreach ($moza as $mozaName) {
                $allMozas->push($mozaName);
            }

            $fileRows[] = [
                'purchase_file_id' => $fileId,
                'file_name' => $file->file_name,
                'moza' => $moza->isEmpty() ? '—' : $moza->implode(', '),
                'items_count' => $file->purchaseItems->count(),
                'total_land_marla' => $total,
                'total_land' => $total > 0 ? LandMeasure::formatAkmsLabelFromMarla($total) : '—',
                'sold_land_marla' => $sold,
                'sold_land' => $sold > 0 ? LandMeasure::formatAkmsLabelFromMarla($sold) : '—',
                'remaining_land_marla' => $remaining,
                'remaining_land' => $total > 0 ? LandMeasure::formatAkmsLabelFromMarla($remaining) : '—',
                'remaining_land_compact' => $remaining > 0 ? LandMeasure::formatAkmsCompactFromMarla($remaining) : '—',
                'status' => $file->saleStatusLabel(),
                'plots' => $plots,
            ];
        }

        $remainingMarla = max(0.0, round($totalMarla - $soldMarla, 6));
        $uniqueMozas = $allMozas->unique()->sort()->values();
        $formulaRemaining = [];
        foreach ($formulaColumns as $column) {
            $plotKey = (string) ($column['plot_key'] ?? '');
            $available = (float) ($formulaAvailableTotals[$plotKey] ?? 0);
            $soldQty = (float) ($formulaSoldTotals[$plotKey] ?? 0);
            $left = (float) ($formulaRemainingTotals[$plotKey] ?? 0);
            $formulaRemaining[$plotKey] = [
                'available_display' => SaleExemptionFileCalculator::formatFileCount($available),
                'sold_display' => $soldQty > 0 ? SaleExemptionFileCalculator::formatFileCount($soldQty) : '—',
                'remaining_display' => SaleExemptionFileCalculator::formatFileCount($left),
                'remaining' => round($left, 4),
                'is_depleted' => $left <= 1e-6 && $available > 1e-6,
            ];
        }

        return [
            'formula_columns' => $formulaColumns,
            'files' => $fileRows,
            'totals' => [
                'total_land' => $totalMarla > 0 ? LandMeasure::formatAkmsLabelFromMarla($totalMarla) : '—',
                'sold_land' => $soldMarla > 0 ? LandMeasure::formatAkmsLabelFromMarla($soldMarla) : '—',
                'remaining_land' => $totalMarla > 0 ? LandMeasure::formatAkmsLabelFromMarla($remainingMarla) : '—',
                'total_land_marla' => round($totalMarla, 6),
                'sold_land_marla' => round($soldMarla, 6),
                'remaining_land_marla' => $remainingMarla,
                'files_count' => count($fileRows),
                'total_khasras' => $totalKhasras,
                'mouzas_count' => $uniqueMozas->count(),
                'mouzas_names' => $uniqueMozas->isEmpty() ? '—' : $uniqueMozas->implode(', '),
                'partially_sold' => collect($fileRows)->where('status', 'Partially Sold')->count(),
                'fully_sold' => collect($fileRows)->where('status', 'Fully Sold')->count(),
                'formula_remaining' => $formulaRemaining,
            ],
        ];
    }

    /**
     * Area balance: Mouza → file → khasra, with land + R1/R2/R3/C4… formula files grouped by Mouza.
     *
     * @param  list<int>  $purchaseFileIds
     * @return array{
     *   formula_columns: list<array<string, mixed>>,
     *   moza_groups: list<array<string, mixed>>,
     *   totals: array{total_land: string, formula_values: array<string, array{display: string, breakdown: string}>}
     * }
     */
    private function buildAreaBalanceByMoza(Project $project, array $purchaseFileIds, SaleExemptionConfig $config): array
    {
        if ($purchaseFileIds === []) {
            return [
                'formula_columns' => [],
                'moza_groups' => [],
                'totals' => [
                    'total_land' => '—',
                    'formula_values' => [],
                ],
            ];
        }

        $sheet = SaleLandMozaGroups::spreadsheetForProject($project, $purchaseFileIds);
        $formulaColumns = collect($sheet['formula_columns'] ?? [])
            ->map(function (array $column) {
                return array_merge($column, [
                    'column_code' => str_replace('.', '', (string) ($column['code'] ?? '')),
                ]);
            })
            ->values()
            ->all();

        $groups = [];
        foreach ($sheet['rows'] ?? [] as $row) {
            $mozaKey = (string) ($row['moza_key'] ?? $row['moza'] ?? '');
            if (! isset($groups[$mozaKey])) {
                $groups[$mozaKey] = [
                    'moza' => $row['moza'] ?? '—',
                    'moza_key' => $mozaKey,
                    'files' => [],
                    'total_land_marla' => 0.0,
                ];
            }

            $groups[$mozaKey]['files'][] = [
                'purchase_file_id' => $row['purchase_file_id'] ?? null,
                'file_name' => $row['file_name'] ?? '—',
                'khasra' => $row['khasra'] ?? '—',
                'land_owner' => $row['land_owner'] ?? '—',
                'total_land' => $row['total_land'] ?? '—',
                'total_land_marla' => (float) ($row['total_land_marla'] ?? 0),
            ];
            $groups[$mozaKey]['total_land_marla'] += (float) ($row['total_land_marla'] ?? 0);
        }

        uasort($groups, fn (array $a, array $b) => strnatcasecmp((string) $a['moza'], (string) $b['moza']));

        $mozaGroups = [];
        foreach ($groups as $group) {
            $marla = (float) $group['total_land_marla'];
            $mozaGroups[] = [
                'moza' => $group['moza'],
                'moza_key' => $group['moza_key'],
                'files' => $group['files'],
                'rowspan' => max(1, count($group['files'])),
                'total_land_marla' => $marla,
                'total_land' => $marla > 0 ? LandMeasure::formatAkmsLabelFromMarla($marla) : '—',
                'formula_values' => $this->formulaValuesForMarla($marla, $config, $formulaColumns),
            ];
        }

        $totalMarla = collect($mozaGroups)->sum(fn (array $g) => (float) $g['total_land_marla']);

        return [
            'formula_columns' => $formulaColumns,
            'moza_groups' => $mozaGroups,
            'totals' => [
                'total_land' => $totalMarla > 0 ? LandMeasure::formatAkmsLabelFromMarla($totalMarla) : '—',
                'total_land_marla' => round($totalMarla, 4),
                'formula_values' => $this->formulaValuesForMarla($totalMarla, $config, $formulaColumns),
            ],
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $formulaColumns
     * @return array<string, array{display: string, breakdown: string}>
     */
    private function formulaValuesForMarla(float $marla, SaleExemptionConfig $config, array $formulaColumns): array
    {
        if ($marla <= 0 || $formulaColumns === []) {
            return [];
        }

        $calculator = SaleExemptionFileCalculator::calculate($marla, $config);
        $byPlot = [];
        foreach ($calculator['rows'] as $calcRow) {
            $byPlot[$calcRow['component_slug'].'|'.$calcRow['plot_slug']] = [
                'display' => SaleExemptionFileCalculator::formatFileCount((float) $calcRow['file_count']),
                'breakdown' => SaleExemptionFileCalculator::formatFileBreakdown(
                    (int) $calcRow['full_files'],
                    (float) $calcRow['fraction_files'],
                    (float) $calcRow['fraction_marla'],
                ),
            ];
        }

        $values = [];
        foreach ($formulaColumns as $column) {
            $key = ($column['component_slug'] ?? '').'|'.($column['plot_slug'] ?? '');
            $plotKey = (string) ($column['plot_key'] ?? $key);
            $values[$plotKey] = $byPlot[$key] ?? [
                'display' => '—',
                'breakdown' => '—',
            ];
        }

        return $values;
    }

    private function shortPlotLabel(string $plotLabel): string
    {
        if (preg_match('/^(\d+)\s*(Kanal|Marla)/i', $plotLabel, $matches)) {
            return $matches[1].strtoupper(substr($matches[2], 0, 1));
        }

        return $plotLabel;
    }

    /** Sheet-style land label, e.g. 8A - 4K - 12M */
    private function formatAkmsSheetFromMarla(float $totalMarla): string
    {
        $eps = 1e-6;
        if ($totalMarla <= 0 && $totalMarla > -$eps) {
            return '0A - 0K - 0M';
        }

        $wholeMarla = (int) floor($totalMarla + $eps);
        $a = intdiv($wholeMarla, 160);
        $r = $wholeMarla - $a * 160;
        $k = intdiv($r, 20);
        $m = $r - $k * 20;

        return sprintf('%dA - %dK - %dM', $a, $k, $m);
    }

    /**
     * @param  list<int>  $soldParts
     */
    private function formatSoldPaymentDisplay(array $soldParts, int $soldQty): string
    {
        if ($soldQty < 1) {
            return '—';
        }

        if (count($soldParts) > 1) {
            return implode(' + ', array_map('strval', $soldParts)).' = '.$soldQty;
        }

        return $soldQty.' =';
    }

    /**
     * @param  list<float>  $balSteps  running remaining after each sold lot
     */
    private function formatFileBalDisplay(float $fileBal, array $balSteps): string
    {
        // When multiple payment lots were sold, show the running leftovers
        // (matches handwritten sheet: "+1.2875 −0.2875").
        if (count($balSteps) >= 2) {
            $parts = [];
            foreach ($balSteps as $i => $step) {
                $parts[] = $this->formatSignedFileCount((float) $step, $i === 0);
            }

            return implode(' ', $parts);
        }

        return $this->formatSignedFileCount($fileBal, true);
    }

    private function formatSignedFileCount(float $value, bool $leadingPlus = true): string
    {
        if (abs($value) < 1e-9) {
            return '0';
        }

        $abs = SaleExemptionFileCalculator::formatFileCount(abs($value));
        if ($value > 0) {
            return $leadingPlus ? '+'.$abs : $abs;
        }

        return '−'.$abs;
    }

    /**
     * @param  list<int>  $purchaseFileIds
     * @return list<array{category: string, sub_category: string, total_amount: float, total_amount_formatted: string}>
     */
    public function buildDaybookSummary(Project $project, array $purchaseFileIds): array
    {
        if ($purchaseFileIds === []) {
            return [];
        }

        $entries = DayBookEntry::query()
            ->linkedToProject($project)
            ->whereIn('purchase_file_id', $purchaseFileIds)
            ->with(['partySubCategory.category'])
            ->get();

        return $entries
            ->groupBy(function (DayBookEntry $entry) {
                $category = $entry->partySubCategory?->category?->name ?? '—';
                $subCategory = $entry->partySubCategory?->name ?? '—';

                return $category.'||'.$subCategory;
            })
            ->map(function (Collection $group) {
                /** @var DayBookEntry $first */
                $first = $group->first();
                $category = $first->partySubCategory?->category?->name ?? '—';
                $subCategory = $first->partySubCategory?->name ?? '—';
                $total = round((float) $group->sum('amount'), 2);

                return [
                    'category' => $category,
                    'sub_category' => $subCategory,
                    'total_amount' => $total,
                    'total_amount_formatted' => 'Rs '.number_format($total, 0),
                ];
            })
            ->sortBy([
                ['category', 'asc'],
                ['sub_category', 'asc'],
            ])
            ->values()
            ->all();
    }

    public function removeForSaleLand(PurchaseFile $purchaseFile): void
    {
        FileSaleLand::query()
            ->where('sale_land_id', $purchaseFile->id)
            ->delete();
    }

    public function formatFileCalculationBreakdown(int $fullFiles, float $fractionFiles, float $fractionMarla): string
    {
        if ($fullFiles < 1 && $fractionFiles <= 0) {
            return '—';
        }

        $parts = [];
        if ($fullFiles > 0) {
            $parts[] = $fullFiles.' File'.($fullFiles === 1 ? '' : 's');
        }
        if ($fractionFiles > 0) {
            $parts[] = SaleExemptionFileCalculator::formatMarla($fractionFiles).' File';
            $parts[] = SaleExemptionFileCalculator::formatMarla($fractionMarla).' Marla';
        }

        return implode(' + ', $parts);
    }
}
