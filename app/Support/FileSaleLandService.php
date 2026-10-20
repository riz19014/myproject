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
use Illuminate\Support\Facades\Schema;
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
     * Re-apply an exemption formula to an open saved sale file.
     * Pass a saved snapshot id, or null to use the current project exemption setup.
     * Completed sale files are locked — reopen first to change exemption.
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

        if ($collective->isCompleted() || ! $collective->isOpen()) {
            throw ValidationException::withMessages([
                'collective' => ['This sale file is completed. Reopen it before choosing or adding an exemption.'],
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

            $mouzaKey = $this->mouzaKeyFromFiles($members);

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
                'mouza_key' => $mouzaKey,
                'mouzas_names' => $leftoverBalance['totals']['mouzas_names'] ?? '—',
                'mouzas_count' => (int) ($leftoverBalance['totals']['mouzas_count'] ?? 0),
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
     * Truly merge selected same-mouza purchase files into ONE purchase file
     * (items + related rows combined). Editable name defaults to combined land
     * (e.g. 12 kanal + 7 kanal → "19 kanal"). Other files in the clump stay.
     *
     * @param  list<int>  $purchaseFileIds
     * @return array{collective: array{id: int, name: string, status: string}, file: array{id: int, name: string}, file_count: int, mouza: string, suggested_name: string}
     */
    public function mergeInnerFiles(
        Project $project,
        array $purchaseFileIds,
        ?string $name = null,
        ?int $preferCollectiveId = null,
    ): array {
        $purchaseFileIds = collect($purchaseFileIds)
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        if (count($purchaseFileIds) < 2) {
            throw ValidationException::withMessages([
                'purchase_file_ids' => ['Select at least two files of the same mouza to merge.'],
            ]);
        }

        $links = FileSaleLand::query()
            ->where('project_id', $project->id)
            ->whereIn('sale_land_id', $purchaseFileIds)
            ->whereNotNull('collective_id')
            ->with(['saleLand.purchaseItems', 'saleLand.dealers', 'collective'])
            ->get()
            ->keyBy(fn (FileSaleLand $row) => (int) $row->sale_land_id);

        if ($links->count() !== count($purchaseFileIds)) {
            throw ValidationException::withMessages([
                'purchase_file_ids' => ['Select files from inside open saved sale files only.'],
            ]);
        }

        $closed = $links->filter(fn (FileSaleLand $row) => ! ($row->collective?->isOpen() ?? false));
        if ($closed->isNotEmpty()) {
            throw ValidationException::withMessages([
                'purchase_file_ids' => ['Only files inside open sale files can be merged. Reopen completed ones first.'],
            ]);
        }

        $memberFiles = $links->map(fn (FileSaleLand $row) => $row->saleLand)->filter()->values();
        if ($memberFiles->count() !== count($purchaseFileIds)) {
            throw ValidationException::withMessages([
                'purchase_file_ids' => ['One or more selected files could not be loaded.'],
            ]);
        }

        $mouzaKey = $this->mouzaKeyFromFiles($memberFiles);
        if ($mouzaKey === '') {
            throw ValidationException::withMessages([
                'purchase_file_ids' => ['Selected files have no mouza.'],
            ]);
        }

        $perFileKeys = $memberFiles->map(fn (PurchaseFile $file) => $this->mouzaKeyFromFiles(collect([$file])));
        if ($perFileKeys->unique()->count() !== 1) {
            throw ValidationException::withMessages([
                'purchase_file_ids' => ['Selected files must share the same mouza.'],
            ]);
        }

        $mouzaLabel = $memberFiles
            ->flatMap(fn (PurchaseFile $file) => $file->purchaseItems->pluck('moza'))
            ->filter(fn ($v) => filled($v))
            ->map(fn ($v) => trim((string) $v))
            ->unique()
            ->sort()
            ->values()
            ->implode(', ');

        $suggestedName = $this->suggestedMergeNameFromFiles($memberFiles);
        $finalName = ($name !== null && trim($name) !== '') ? trim($name) : $suggestedName;

        $keeperLink = $this->resolveMergeKeeperLink($links, $preferCollectiveId);
        $keeper = $keeperLink->saleLand;
        $keeperCollective = $keeperLink->collective;
        if (! $keeper || ! $keeperCollective) {
            throw ValidationException::withMessages([
                'purchase_file_ids' => ['Could not resolve a file to keep for this merge.'],
            ]);
        }

        $donorIds = collect($purchaseFileIds)
            ->reject(fn (int $id) => $id === (int) $keeper->id)
            ->values()
            ->all();

        $result = DB::transaction(function () use (
            $project,
            $links,
            $keeper,
            $keeperLink,
            $keeperCollective,
            $donorIds,
            $finalName,
        ) {
            $meta = is_array($keeper->merge_meta) ? $keeper->merge_meta : [];
            if (! isset($meta['original_name']) || trim((string) $meta['original_name']) === '') {
                $meta['original_name'] = (string) $keeper->file_name;
            }
            $meta['parts'] = is_array($meta['parts'] ?? null) ? array_values($meta['parts']) : [];
            $keeper->merge_meta = $meta;
            $keeper->save();

            foreach ($donorIds as $donorId) {
                /** @var FileSaleLand|null $donorLink */
                $donorLink = $links->get($donorId);
                $donor = $donorLink?->saleLand;
                if (! $donor) {
                    continue;
                }
                $this->absorbPurchaseFileInto($project, $keeper, $donor, $donorLink);
            }

            $keeper->update(['file_name' => $finalName]);
            $keeper->load('purchaseItems');

            // Keep keeper on its clump (re-fetch in case the in-memory link was stale).
            $freshKeeperLink = FileSaleLand::query()
                ->where('project_id', $project->id)
                ->where('sale_land_id', $keeper->id)
                ->first();
            if ($freshKeeperLink) {
                $freshKeeperLink->update(['collective_id' => $keeperCollective->id]);
            } else {
                FileSaleLand::create([
                    'project_id' => $project->id,
                    'sale_land_id' => $keeper->id,
                    'collective_id' => $keeperCollective->id,
                ]);
            }

            $touchedCollectiveIds = $links
                ->pluck('collective_id')
                ->map(fn ($id) => (int) $id)
                ->push((int) $keeperCollective->id)
                ->unique()
                ->values()
                ->all();

            foreach ($touchedCollectiveIds as $collectiveId) {
                $remaining = FileSaleLand::query()->where('collective_id', $collectiveId)->count();
                if ($remaining === 0) {
                    FileSaleCollective::query()->whereKey($collectiveId)->delete();

                    continue;
                }
                $collective = FileSaleCollective::query()->find($collectiveId);
                if ($collective) {
                    $this->refreshCollectiveTotals($collective);
                }
            }

            // If the kept clump now only has this one file, rename the clump to match.
            $keeperCollective = $keeperCollective->fresh();
            if ($keeperCollective
                && FileSaleLand::query()->where('collective_id', $keeperCollective->id)->count() === 1
            ) {
                $this->renameCollectiveUnique($project, $keeperCollective, $finalName);
                $keeperCollective = $keeperCollective->fresh();
            }

            return [
                'collective' => $keeperCollective,
                'file' => $keeper->fresh(),
            ];
        });

        /** @var FileSaleCollective $collective */
        $collective = $result['collective'];
        /** @var PurchaseFile $file */
        $file = $result['file'];

        return [
            'collective' => [
                'id' => (int) $collective->id,
                'name' => $collective->name,
                'status' => $collective->status,
            ],
            'file' => [
                'id' => (int) $file->id,
                'name' => $file->file_name,
            ],
            'file_count' => 1,
            'mouza' => $mouzaLabel !== '' ? $mouzaLabel : '—',
            'suggested_name' => $suggestedName,
        ];
    }

    /**
     * @param  Collection<int, FileSaleLand>  $links  keyed by sale_land_id
     */
    private function resolveMergeKeeperLink(Collection $links, ?int $preferCollectiveId): FileSaleLand
    {
        $ordered = $links->sortBy(fn (FileSaleLand $row) => (int) $row->sale_land_id)->values();

        if ($preferCollectiveId !== null && $preferCollectiveId > 0) {
            $fromPreferred = $ordered->first(
                fn (FileSaleLand $row) => (int) $row->collective_id === $preferCollectiveId
            );
            if ($fromPreferred) {
                return $fromPreferred;
            }
        }

        $bestCollectiveId = $links
            ->groupBy(fn (FileSaleLand $row) => (int) $row->collective_id)
            ->map(fn (Collection $group) => $group->count())
            ->sortDesc()
            ->keys()
            ->first();

        $fromBest = $ordered->first(
            fn (FileSaleLand $row) => (int) $row->collective_id === (int) $bestCollectiveId
        );

        return $fromBest ?? $ordered->first();
    }

    private function absorbPurchaseFileInto(
        Project $project,
        PurchaseFile $keeper,
        PurchaseFile $donor,
        FileSaleLand $donorLink,
    ): void {
        if ((int) $keeper->id === (int) $donor->id) {
            return;
        }
        if ((int) $keeper->project_id !== (int) $project->id
            || (int) $donor->project_id !== (int) $project->id) {
            throw ValidationException::withMessages([
                'purchase_file_ids' => ['Selected files must belong to this project.'],
            ]);
        }

        $donor->loadMissing(['purchaseItems', 'dealers']);
        $keeper->loadMissing('dealers');

        $part = $this->snapshotPurchaseFileForMerge($donor, (int) $donorLink->collective_id);

        // Move khasra / purchase lines onto the kept file.
        DB::table('purchase_items')
            ->where('purchase_file_id', $donor->id)
            ->update(['purchase_file_id' => $keeper->id]);

        DB::table('sales')
            ->where('purchase_file_id', $donor->id)
            ->update(['purchase_file_id' => $keeper->id]);

        DB::table('day_book_entries')
            ->where('purchase_file_id', $donor->id)
            ->update(['purchase_file_id' => $keeper->id]);

        DB::table('purchase_file_documents')
            ->where('purchase_file_id', $donor->id)
            ->update(['purchase_file_id' => $keeper->id]);

        // Partners: unique on party_id + project_id + purchase_file_id — drop collisions.
        $keeperPartnerPartyIds = DB::table('project_partners')
            ->where('purchase_file_id', $keeper->id)
            ->pluck('party_id')
            ->all();
        DB::table('project_partners')
            ->where('purchase_file_id', $donor->id)
            ->when($keeperPartnerPartyIds !== [], fn ($q) => $q->whereNotIn('party_id', $keeperPartnerPartyIds))
            ->update(['purchase_file_id' => $keeper->id]);
        DB::table('project_partners')
            ->where('purchase_file_id', $donor->id)
            ->delete();

        // Moza overrides: drop donor rows that would collide on unique key.
        $keeperMozaKeys = DB::table('purchase_file_sale_land_moza_overrides')
            ->where('purchase_file_id', $keeper->id)
            ->pluck('moza_key')
            ->all();
        DB::table('purchase_file_sale_land_moza_overrides')
            ->where('purchase_file_id', $donor->id)
            ->when($keeperMozaKeys !== [], fn ($q) => $q->whereNotIn('moza_key', $keeperMozaKeys))
            ->update(['purchase_file_id' => $keeper->id]);
        DB::table('purchase_file_sale_land_moza_overrides')
            ->where('purchase_file_id', $donor->id)
            ->delete();

        if (Schema::hasTable('file_sale_records')) {
            DB::table('file_sale_records')
                ->where('purchase_file_id', $donor->id)
                ->update(['purchase_file_id' => $keeper->id]);
        }

        // Merge dealers (unique purchase_file_id + party_id).
        $dealerDeltas = [];
        foreach ($donor->dealers as $dealer) {
            $partyId = (int) $dealer->id;
            $commission = (float) ($dealer->pivot->commission_rs ?? 0);
            $existing = $keeper->dealers->firstWhere('id', $partyId);
            $dealerDeltas[] = [
                'party_id' => $partyId,
                'added' => $existing === null,
                'commission_added' => $commission,
            ];
            if ($existing) {
                $keeper->dealers()->updateExistingPivot($partyId, [
                    'commission_rs' => round((float) ($existing->pivot->commission_rs ?? 0) + $commission, 2),
                ]);
            } else {
                $keeper->dealers()->attach($partyId, ['commission_rs' => $commission]);
            }
        }
        $part['dealer_deltas'] = $dealerDeltas;
        $donor->dealers()->detach();

        $meta = is_array($keeper->merge_meta) ? $keeper->merge_meta : [];
        $meta['parts'] = array_values(array_merge(
            is_array($meta['parts'] ?? null) ? $meta['parts'] : [],
            [$part]
        ));
        $keeper->merge_meta = $meta;
        $keeper->save();

        // Remove donor from file-sale clump, then delete the purchase file.
        $donorLink->delete();
        $donor->delete();
    }

    /**
     * @return array<string, mixed>
     */
    private function snapshotPurchaseFileForMerge(PurchaseFile $file, int $collectiveId): array
    {
        $file->loadMissing(['purchaseItems', 'dealers']);

        $partnerRows = DB::table('project_partners')
            ->where('purchase_file_id', $file->id)
            ->get()
            ->map(fn ($row) => (array) $row)
            ->all();

        return [
            'file_name' => (string) $file->file_name,
            'file_date' => $file->file_date?->toDateString(),
            'sale_land_at' => $file->sale_land_at?->toDateTimeString(),
            'collective_id' => $collectiveId,
            'purchase_item_ids' => $file->purchaseItems->pluck('id')->map(fn ($id) => (int) $id)->values()->all(),
            'day_book_entry_ids' => DB::table('day_book_entries')
                ->where('purchase_file_id', $file->id)
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->values()
                ->all(),
            'sale_ids' => DB::table('sales')
                ->where('purchase_file_id', $file->id)
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->values()
                ->all(),
            'document_ids' => DB::table('purchase_file_documents')
                ->where('purchase_file_id', $file->id)
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->values()
                ->all(),
            'moza_override_ids' => DB::table('purchase_file_sale_land_moza_overrides')
                ->where('purchase_file_id', $file->id)
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->values()
                ->all(),
            'file_sale_record_ids' => Schema::hasTable('file_sale_records')
                ? DB::table('file_sale_records')
                    ->where('purchase_file_id', $file->id)
                    ->pluck('id')
                    ->map(fn ($id) => (int) $id)
                    ->values()
                    ->all()
                : [],
            'partner_rows' => $partnerRows,
            'dealers' => $file->dealers->map(fn ($dealer) => [
                'party_id' => (int) $dealer->id,
                'commission_rs' => (float) ($dealer->pivot->commission_rs ?? 0),
            ])->values()->all(),
            'dealer_deltas' => [],
        ];
    }

    /**
     * @param  Collection<int, PurchaseFile>  $files
     */
    private function suggestedMergeNameFromFiles(Collection $files): string
    {
        $totalMarla = round((float) $files->sum(
            fn (PurchaseFile $file) => (float) $file->totalLandMarla()
        ), 6);

        return mb_substr(LandMeasure::formatSpokenKanalMarlaFromMarla($totalMarla), 0, 150);
    }

    private function renameCollectiveUnique(Project $project, FileSaleCollective $collective, string $name): void
    {
        $name = trim($name);
        if ($name === '' || $name === $collective->name) {
            return;
        }

        if (FileSaleCollective::query()
            ->where('project_id', $project->id)
            ->where('name', $name)
            ->where('id', '!=', $collective->id)
            ->exists()) {
            // Fall back to a unique variant instead of failing the whole merge.
            $name = $this->uniqueCollectiveNameForFile($project, $name);
        }

        if ($name === $collective->name) {
            return;
        }

        $collective->update(['name' => $name]);
    }

    /**
     * Reverse a purchase-file merge (restore original files from merge_meta),
     * or split a non-merged file out of a multi-file clump into its own sale file.
     *
     * @param  list<int>  $purchaseFileIds
     * @return array{file_count: int, collective_count: int, collective_ids: list<int>, mode: string}
     */
    public function unmergeInnerFiles(Project $project, array $purchaseFileIds): array
    {
        $purchaseFileIds = collect($purchaseFileIds)
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        if ($purchaseFileIds === []) {
            throw ValidationException::withMessages([
                'purchase_file_ids' => ['Select at least one file to unmerge.'],
            ]);
        }

        $links = FileSaleLand::query()
            ->where('project_id', $project->id)
            ->whereIn('sale_land_id', $purchaseFileIds)
            ->whereNotNull('collective_id')
            ->with(['saleLand.purchaseItems', 'saleLand.dealers', 'collective'])
            ->orderBy('id')
            ->get();

        if ($links->count() !== count($purchaseFileIds)) {
            throw ValidationException::withMessages([
                'purchase_file_ids' => ['Select files from inside open saved sale files only.'],
            ]);
        }

        $closed = $links->filter(fn (FileSaleLand $row) => ! ($row->collective?->isOpen() ?? false));
        if ($closed->isNotEmpty()) {
            throw ValidationException::withMessages([
                'purchase_file_ids' => ['Only files inside open sale files can be unmerged. Reopen completed ones first.'],
            ]);
        }

        $mergedLinks = $links->filter(fn (FileSaleLand $row) => $row->saleLand?->hasMergeParts());
        if ($mergedLinks->isNotEmpty()) {
            return $this->reverseMergedPurchaseFiles($project, $mergedLinks);
        }

        return $this->splitFilesIntoOwnCollectives($project, $links);
    }

    /**
     * @param  Collection<int, FileSaleLand>  $links
     * @return array{file_count: int, collective_count: int, collective_ids: list<int>, mode: string}
     */
    private function reverseMergedPurchaseFiles(Project $project, Collection $links): array
    {
        $createdIds = DB::transaction(function () use ($project, $links) {
            $restoredFileIds = [];
            $touchedCollectiveIds = [];

            foreach ($links as $link) {
                $keeper = $link->saleLand;
                if (! $keeper || ! $keeper->hasMergeParts()) {
                    continue;
                }

                $collectiveId = (int) $link->collective_id;
                $touchedCollectiveIds[$collectiveId] = true;
                $meta = is_array($keeper->merge_meta) ? $keeper->merge_meta : [];
                $parts = array_values($meta['parts'] ?? []);
                $originalName = trim((string) ($meta['original_name'] ?? ''));

                foreach ($parts as $part) {
                    if (! is_array($part)) {
                        continue;
                    }
                    $restored = $this->restorePurchaseFileFromMergePart(
                        $project,
                        $keeper,
                        $part,
                        $collectiveId
                    );
                    $restoredFileIds[] = (int) $restored->id;
                }

                if ($originalName !== '' && $originalName !== $keeper->file_name) {
                    $keeper->update([
                        'file_name' => $this->uniquePurchaseFileName($project, $originalName, (int) $keeper->id),
                    ]);
                }
                $keeper->update(['merge_meta' => null]);
                $keeper->load('purchaseItems');
            }

            foreach (array_keys($touchedCollectiveIds) as $collectiveId) {
                $collective = FileSaleCollective::query()->find($collectiveId);
                if ($collective) {
                    $this->refreshCollectiveTotals($collective);
                }
            }

            return $restoredFileIds;
        });

        $anchorCollectiveIds = $links
            ->pluck('collective_id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        return [
            'file_count' => count($createdIds) + $links->count(),
            'collective_count' => count($anchorCollectiveIds),
            'collective_ids' => $anchorCollectiveIds,
            'mode' => 'file',
        ];
    }

    /**
     * @param  array<string, mixed>  $part
     */
    private function restorePurchaseFileFromMergePart(
        Project $project,
        PurchaseFile $keeper,
        array $part,
        int $collectiveId,
    ): PurchaseFile {
        $name = $this->uniquePurchaseFileName(
            $project,
            trim((string) ($part['file_name'] ?? 'Restored file'))
        );

        $restored = PurchaseFile::create([
            'project_id' => $project->id,
            'file_name' => $name,
            'file_date' => $part['file_date'] ?? null,
            'sale_land_at' => $part['sale_land_at'] ?? $keeper->sale_land_at,
            'merge_meta' => null,
        ]);

        $itemIds = collect($part['purchase_item_ids'] ?? [])->map(fn ($id) => (int) $id)->filter()->all();
        if ($itemIds !== []) {
            DB::table('purchase_items')
                ->where('purchase_file_id', $keeper->id)
                ->whereIn('id', $itemIds)
                ->update(['purchase_file_id' => $restored->id]);
        }

        $saleIds = collect($part['sale_ids'] ?? [])->map(fn ($id) => (int) $id)->filter()->all();
        if ($saleIds !== []) {
            DB::table('sales')
                ->where('purchase_file_id', $keeper->id)
                ->whereIn('id', $saleIds)
                ->update(['purchase_file_id' => $restored->id]);
        }

        $daybookIds = collect($part['day_book_entry_ids'] ?? [])->map(fn ($id) => (int) $id)->filter()->all();
        if ($daybookIds !== []) {
            DB::table('day_book_entries')
                ->where('purchase_file_id', $keeper->id)
                ->whereIn('id', $daybookIds)
                ->update(['purchase_file_id' => $restored->id]);
        }

        $documentIds = collect($part['document_ids'] ?? [])->map(fn ($id) => (int) $id)->filter()->all();
        if ($documentIds !== []) {
            DB::table('purchase_file_documents')
                ->where('purchase_file_id', $keeper->id)
                ->whereIn('id', $documentIds)
                ->update(['purchase_file_id' => $restored->id]);
        }

        $mozaIds = collect($part['moza_override_ids'] ?? [])->map(fn ($id) => (int) $id)->filter()->all();
        if ($mozaIds !== []) {
            DB::table('purchase_file_sale_land_moza_overrides')
                ->where('purchase_file_id', $keeper->id)
                ->whereIn('id', $mozaIds)
                ->update(['purchase_file_id' => $restored->id]);
        }

        $recordIds = collect($part['file_sale_record_ids'] ?? [])->map(fn ($id) => (int) $id)->filter()->all();
        if ($recordIds !== [] && Schema::hasTable('file_sale_records')) {
            DB::table('file_sale_records')
                ->where('purchase_file_id', $keeper->id)
                ->whereIn('id', $recordIds)
                ->update(['purchase_file_id' => $restored->id]);
        }

        foreach (($part['partner_rows'] ?? []) as $row) {
            if (! is_array($row)) {
                continue;
            }
            $partyId = (int) ($row['party_id'] ?? 0);
            if ($partyId < 1) {
                continue;
            }
            $exists = DB::table('project_partners')
                ->where('purchase_file_id', $restored->id)
                ->where('party_id', $partyId)
                ->where('project_id', $project->id)
                ->exists();
            if ($exists) {
                continue;
            }
            DB::table('project_partners')->insert([
                'project_id' => $project->id,
                'purchase_file_id' => $restored->id,
                'party_id' => $partyId,
                'investment_amount' => $row['investment_amount'] ?? 0,
                'share_percentage' => $row['share_percentage'] ?? 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $keeper->load('dealers');
        foreach (($part['dealer_deltas'] ?? []) as $delta) {
            if (! is_array($delta)) {
                continue;
            }
            $partyId = (int) ($delta['party_id'] ?? 0);
            $commissionAdded = (float) ($delta['commission_added'] ?? 0);
            if ($partyId < 1) {
                continue;
            }
            $existing = $keeper->dealers->firstWhere('id', $partyId);
            if (! $existing) {
                continue;
            }
            if (! empty($delta['added'])) {
                $keeper->dealers()->detach($partyId);
            } else {
                $next = round((float) ($existing->pivot->commission_rs ?? 0) - $commissionAdded, 2);
                if ($next <= 0) {
                    $keeper->dealers()->detach($partyId);
                } else {
                    $keeper->dealers()->updateExistingPivot($partyId, ['commission_rs' => $next]);
                }
            }
        }
        $keeper->unsetRelation('dealers');

        foreach (($part['dealers'] ?? []) as $dealer) {
            if (! is_array($dealer)) {
                continue;
            }
            $partyId = (int) ($dealer['party_id'] ?? 0);
            if ($partyId < 1) {
                continue;
            }
            $restored->dealers()->syncWithoutDetaching([
                $partyId => ['commission_rs' => (float) ($dealer['commission_rs'] ?? 0)],
            ]);
        }

        FileSaleLand::create([
            'project_id' => $project->id,
            'sale_land_id' => $restored->id,
            'collective_id' => $collectiveId,
        ]);

        return $restored->fresh();
    }

    /**
     * @param  Collection<int, FileSaleLand>  $links
     * @return array{file_count: int, collective_count: int, collective_ids: list<int>, mode: string}
     */
    private function splitFilesIntoOwnCollectives(Project $project, Collection $links): array
    {
        $sourceCounts = FileSaleLand::query()
            ->whereIn('collective_id', $links->pluck('collective_id')->unique()->all())
            ->selectRaw('collective_id, COUNT(*) as file_count')
            ->groupBy('collective_id')
            ->pluck('file_count', 'collective_id');

        $splittable = $links->filter(function (FileSaleLand $row) use ($sourceCounts) {
            return (int) ($sourceCounts[$row->collective_id] ?? 0) > 1;
        });

        if ($splittable->isEmpty()) {
            throw ValidationException::withMessages([
                'purchase_file_ids' => ['Selected file(s) are not merged and are already separate.'],
            ]);
        }

        $createdIds = DB::transaction(function () use ($project, $splittable) {
            $ids = [];
            $touchedSourceIds = [];

            foreach ($splittable as $link) {
                $file = $link->saleLand;
                if (! $file) {
                    continue;
                }

                $sourceId = (int) $link->collective_id;
                $touchedSourceIds[$sourceId] = true;

                $name = $this->uniqueCollectiveNameForFile($project, (string) $file->file_name);
                $collective = $this->createCollectiveHeader($project, $name, collect([$file]));

                $link->update(['collective_id' => $collective->id]);
                $this->refreshCollectiveTotals($collective->fresh());
                $ids[] = (int) $collective->id;
            }

            foreach (array_keys($touchedSourceIds) as $sourceId) {
                $remaining = FileSaleLand::query()->where('collective_id', $sourceId)->count();
                if ($remaining === 0) {
                    FileSaleCollective::query()->whereKey($sourceId)->delete();
                } else {
                    $source = FileSaleCollective::query()->find($sourceId);
                    if ($source) {
                        $this->refreshCollectiveTotals($source);
                    }
                }
            }

            return $ids;
        });

        return [
            'file_count' => count($createdIds),
            'collective_count' => count($createdIds),
            'collective_ids' => $createdIds,
            'mode' => 'clump',
        ];
    }

    private function uniquePurchaseFileName(Project $project, string $base, ?int $ignoreId = null): string
    {
        $base = trim($base);
        if ($base === '') {
            $base = 'Restored file';
        }
        $base = mb_substr($base, 0, 140);
        $name = $base;
        $i = 2;
        while (PurchaseFile::query()
            ->where('project_id', $project->id)
            ->where('file_name', $name)
            ->when($ignoreId !== null, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->exists()) {
            $suffix = ' ('.$i.')';
            $name = mb_substr($base, 0, 150 - mb_strlen($suffix)).$suffix;
            $i++;
        }

        return $name;
    }

    private function uniqueCollectiveNameForFile(Project $project, string $fileName): string
    {
        $base = trim($fileName);
        if ($base === '') {
            return $this->nextCollectiveName($project);
        }

        $base = mb_substr($base, 0, 140);
        $name = $base;
        $i = 2;
        while (FileSaleCollective::query()
            ->where('project_id', $project->id)
            ->where('name', $name)
            ->exists()) {
            $suffix = ' ('.$i.')';
            $name = mb_substr($base, 0, 150 - mb_strlen($suffix)).$suffix;
            $i++;
        }

        return $name;
    }

    /**
     * @param  Collection<int, PurchaseFile|null>  $files
     */
    private function mouzaKeyFromFiles(Collection $files): string
    {
        return $files
            ->filter()
            ->flatMap(fn (PurchaseFile $file) => $file->purchaseItems->pluck('moza'))
            ->filter(fn ($v) => filled($v))
            ->map(fn ($v) => mb_strtolower(trim((string) $v)))
            ->unique()
            ->sort()
            ->values()
            ->implode('|');
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
                'grand_total_amount_short' => SaleExemptionFileCalculator::formatRsShort($grandTotalAmount),
                'sale_files_amount' => round($saleFilesAmount, 2),
                'sale_files_amount_formatted' => 'Rs '.number_format($saleFilesAmount, 0),
                'sale_files_amount_short' => SaleExemptionFileCalculator::formatRsShort($saleFilesAmount),
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
            'suggested_sale_file_name' => $this->nextCollectiveName($project),
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

        $sheet = SaleLandMozaGroups::spreadsheetForProject($project, $purchaseFileIds, $config);
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
                'mouza_key' => $moza->isEmpty()
                    ? ''
                    : $moza->map(fn ($v) => mb_strtolower(trim((string) $v)))->sort()->values()->implode('|'),
                'items_count' => $file->purchaseItems->count(),
                'total_land_marla' => $total,
                'total_land' => $total > 0 ? LandMeasure::formatAkmsLabelFromMarla($total) : '—',
                'sold_land_marla' => $sold,
                'sold_land' => $sold > 0 ? LandMeasure::formatAkmsLabelFromMarla($sold) : '—',
                'remaining_land_marla' => $remaining,
                'remaining_land' => $total > 0 ? LandMeasure::formatAkmsLabelFromMarla($remaining) : '—',
                'remaining_land_compact' => $remaining > 0 ? LandMeasure::formatAkmsCompactFromMarla($remaining) : '—',
                'status' => $file->saleStatusLabel(),
                'is_merged' => $file->hasMergeParts(),
                'can_file_unmerge' => $file->hasMergeParts(),
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

        $sheet = SaleLandMozaGroups::spreadsheetForProject($project, $purchaseFileIds, $config);
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

    /**
     * Delete a sale-file clump and return all its purchase files to Sale Land
     * (keeps sale_land_at; removes File Sale links so they reappear in Ready to move).
     *
     * @return array{collective_name: string, file_count: int, file_names: list<string>}
     */
    public function dissolveCollectiveToSaleLand(Project $project, FileSaleCollective $collective): array
    {
        if ((int) $collective->project_id !== (int) $project->id) {
            throw ValidationException::withMessages([
                'collective' => ['Sale file does not belong to this project.'],
            ]);
        }

        $links = FileSaleLand::query()
            ->where('project_id', $project->id)
            ->where('collective_id', $collective->id)
            ->with('saleLand')
            ->get();

        $blocked = [];
        foreach ($links as $link) {
            $file = $link->saleLand;
            if (! $file) {
                continue;
            }
            $hasSales = Sale::query()
                ->where('project_id', $project->id)
                ->where('purchase_file_id', $file->id)
                ->where('sale_type', Sale::TYPE_SALE_LAND)
                ->exists();
            if ($hasSales) {
                $blocked[] = $file->file_name;
            }
        }

        if ($blocked !== []) {
            throw ValidationException::withMessages([
                'collective' => [
                    'Cannot delete this clump because these file(s) already have sale land sales: '
                    .implode(', ', $blocked)
                    .'.',
                ],
            ]);
        }

        $fileNames = $links
            ->map(fn (FileSaleLand $link) => $link->saleLand?->file_name)
            ->filter()
            ->values()
            ->all();

        $collectiveName = $collective->name;

        DB::transaction(function () use ($links, $collective) {
            FileSaleLand::query()
                ->whereIn('id', $links->pluck('id')->all())
                ->delete();
            $collective->delete();
        });

        return [
            'collective_name' => $collectiveName,
            'file_count' => count($fileNames),
            'file_names' => $fileNames,
        ];
    }

    /**
     * Remove a purchase file from an open sale-file collective back to Sale Land
     * (keeps sale_land_at; deletes the File Sale link).
     *
     * @return array{file_name: string, collective_name: string, collective_id: int, remaining_files: int}
     */
    public function excludeFromCollectiveToSaleLand(
        Project $project,
        FileSaleCollective $collective,
        PurchaseFile $purchaseFile
    ): array {
        if ((int) $collective->project_id !== (int) $project->id) {
            throw ValidationException::withMessages([
                'collective' => ['Sale file does not belong to this project.'],
            ]);
        }

        if ((int) $purchaseFile->project_id !== (int) $project->id) {
            throw ValidationException::withMessages([
                'purchase_file_id' => ['File does not belong to this project.'],
            ]);
        }

        if (! $collective->isOpen()) {
            throw ValidationException::withMessages([
                'collective' => ['Only open sale files can exclude files. Reopen first.'],
            ]);
        }

        $link = FileSaleLand::query()
            ->where('project_id', $project->id)
            ->where('collective_id', $collective->id)
            ->where('sale_land_id', $purchaseFile->id)
            ->first();

        if (! $link) {
            throw ValidationException::withMessages([
                'purchase_file_id' => ['This file is not in the selected sale file.'],
            ]);
        }

        $hasSales = Sale::query()
            ->where('project_id', $project->id)
            ->where('purchase_file_id', $purchaseFile->id)
            ->where('sale_type', Sale::TYPE_SALE_LAND)
            ->exists();

        if ($hasSales) {
            throw ValidationException::withMessages([
                'purchase_file_id' => ['This file already has sale land sales recorded. It cannot be excluded.'],
            ]);
        }

        DB::transaction(function () use ($link, $collective) {
            $link->delete();
            $this->refreshCollectiveTotals($collective->fresh());
        });

        $remaining = FileSaleLand::query()
            ->where('collective_id', $collective->id)
            ->count();

        return [
            'file_name' => $purchaseFile->file_name,
            'collective_name' => $collective->name,
            'collective_id' => (int) $collective->id,
            'remaining_files' => $remaining,
        ];
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
