<?php

namespace App\Support;

use App\Models\DayBookEntry;
use App\Models\FileSaleLand;
use App\Models\Project;
use App\Models\PurchaseFile;
use App\Models\Sale;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

final class FileSaleLandService
{
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
     * @param  list<int>  $purchaseFileIds
     * @return array{moved: list<int>, skipped: list<int>, moved_ids: list<int>}
     */
    public function moveToFileSale(Project $project, array $purchaseFileIds): array
    {
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

        $files = PurchaseFile::query()
            ->where('project_id', $project->id)
            ->whereIn('id', $purchaseFileIds)
            ->whereNotNull('sale_land_at')
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

        $moved = [];
        $skipped = [];

        foreach ($purchaseFileIds as $purchaseFileId) {
            if (in_array($purchaseFileId, $alreadyMoved, true)) {
                $skipped[] = $purchaseFileId;

                continue;
            }

            FileSaleLand::create([
                'project_id' => $project->id,
                'sale_land_id' => $purchaseFileId,
            ]);
            $moved[] = $purchaseFileId;
        }

        return [
            'moved' => $moved,
            'skipped' => $skipped,
            'moved_ids' => $this->movedSaleLandIds($project),
        ];
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
            ->with(['purchaseItems', 'fileSaleLand'])
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
     *   moved_files: list<array{id: int, name: string}>
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

        return [
            'rows' => $rows,
            'files_land_columns' => $filesLandColumns,
            'totals' => [
                'total_land_area' => $totalMarla > 0 ? LandMeasure::formatAkmsLabelFromMarla($totalMarla) : '—',
                'total_land_compact' => $totalMarla > 0 ? LandMeasure::formatAkmsCompactFromMarla($totalMarla) : '—',
                'total_land_marla' => round($totalMarla, 4),
                'total_files' => $totalFilesCount > 0 ? SaleExemptionFileCalculator::formatFileCount($totalFilesCount) : '—',
                'total_files_count' => round($totalFilesCount, 4),
                'grand_total_amount' => round($grandTotalAmount, 2),
                'grand_total_amount_formatted' => 'Rs '.number_format($grandTotalAmount, 0),
                'sale_files_amount' => round($saleFilesAmount, 2),
                'sale_files_amount_formatted' => 'Rs '.number_format($saleFilesAmount, 0),
            ],
            'daybook_rows' => $this->buildDaybookSummary($project, $purchaseFileIds),
            'moved_files' => $files->map(fn (PurchaseFile $file) => [
                'id' => $file->id,
                'name' => $file->file_name,
            ])->values()->all(),
        ];
    }

    private function shortPlotLabel(string $plotLabel): string
    {
        if (preg_match('/^(\d+)\s*(Kanal|Marla)/i', $plotLabel, $matches)) {
            return $matches[1].strtoupper(substr($matches[2], 0, 1));
        }

        return $plotLabel;
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
