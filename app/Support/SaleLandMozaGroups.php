<?php

namespace App\Support;

use App\Models\Project;
use App\Models\PurchaseFile;
use App\Models\PurchaseItem;
use Illuminate\Support\Collection;

final class SaleLandMozaGroups
{
    /**
     * Flat spreadsheet: one row per Mouza, dynamic formula-file columns across the top.
     * Only includes purchase files already marked as sale land.
     *
     * @return array{
     *   formula_columns: list<array{plot_key: string, code: string, short_label: string, plot_label: string, component_label: string}>,
     *   rows: list<array<string, mixed>>,
     *   formula_totals: array{total_land: string, formula_values: array<string, array{display: string, breakdown: string}>}
     * }
     */
    public static function spreadsheetForProject(
        Project $project,
        ?array $purchaseFileIds = null,
        ?SaleExemptionConfig $config = null,
    ): array {
        $config ??= SaleExemptionConfig::forProject($project);
        $formulaColumns = self::formulaColumnsFromConfig($config);

        $filesQuery = $project->purchaseFiles()
            ->whereNotNull('sale_land_at')
            ->orderBy('file_name')
            ->with([
                'purchaseItems' => fn ($q) => $q->with('party'),
                'saleLandMozaOverrides',
            ]);

        if ($purchaseFileIds !== null && $purchaseFileIds !== []) {
            $filesQuery->whereIn('id', $purchaseFileIds);
        }

        $files = $filesQuery->get();

        $rows = [];
        $sr = 1;

        foreach ($files as $file) {
            $fileMozaRows = [];

            foreach (self::groupItemsByMoza($file->purchaseItems) as $mozaKey => $items) {
                $mozaMarla = (float) $items->sum(fn (PurchaseItem $item) => (float) $item->land_area_marla);
                if ($mozaMarla <= 0) {
                    continue;
                }

                $mozaLabel = $mozaKey !== '' ? $mozaKey : '—';
                $ownerNames = $items
                    ->map(fn (PurchaseItem $item) => $item->party?->name)
                    ->filter()
                    ->unique()
                    ->values();
                $khasras = $items
                    ->map(fn (PurchaseItem $item) => trim((string) ($item->khasra ?? '')))
                    ->filter()
                    ->unique()
                    ->values();

                $calculator = SaleExemptionFileCalculator::calculate($mozaMarla, $config);
                $formulaValues = [];
                foreach ($calculator['rows'] as $calcRow) {
                    $key = self::plotKey($calcRow['component_slug'], $calcRow['plot_slug']);
                    $fileCount = (float) $calcRow['file_count'];
                    $formulaValues[$key] = [
                        'file_count' => $fileCount,
                        'display' => SaleExemptionFileCalculator::formatFileCount($fileCount),
                        'breakdown' => SaleExemptionFileCalculator::formatFileBreakdown(
                            $calcRow['full_files'],
                            $calcRow['fraction_files'],
                            $calcRow['fraction_marla'],
                        ),
                    ];
                }

                $override = $file->saleLandMozaOverrides->firstWhere('moza_key', $mozaKey);
                $landOwner = $ownerNames->isEmpty() ? '—' : $ownerNames->implode(', ');
                $defaultLandProvider = $file->file_name;
                $defaultTransferTo = $project->name;

                $fileMozaRows[] = [
                    'sr' => $sr++,
                    'purchase_file_id' => $file->id,
                    'moza_key' => $mozaKey,
                    'file_name' => $file->file_name,
                    'land_provider' => $override?->land_provider ?: $defaultLandProvider,
                    'land_owner' => $landOwner,
                    'transfer_to' => $override?->transfer_to ?: $defaultTransferTo,
                    'moza' => $mozaLabel,
                    'khasra' => $khasras->isEmpty() ? '—' : $khasras->implode(', '),
                    'total_land' => LandMeasure::formatAkmsLabelFromMarla($mozaMarla),
                    'total_land_marla' => $mozaMarla,
                    'formula_values' => $formulaValues,
                    'show_file_name' => false,
                    'file_name_rowspan' => 1,
                ];
            }

            if ($fileMozaRows !== []) {
                $fileMozaRows[0]['show_file_name'] = true;
                $fileMozaRows[0]['file_name_rowspan'] = count($fileMozaRows);
            }

            $rows = array_merge($rows, $fileMozaRows);
        }

        return [
            'formula_columns' => $formulaColumns,
            'rows' => $rows,
            'formula_totals' => self::formulaTotalsForRows($rows, $config),
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return array{total_land: string, formula_values: array<string, array{display: string, breakdown: string}>}
     */
    private static function formulaTotalsForRows(array $rows, SaleExemptionConfig $config): array
    {
        $totalMarla = 0.0;
        foreach ($rows as $row) {
            $totalMarla += (float) ($row['total_land_marla'] ?? 0);
        }

        if ($totalMarla <= 0) {
            return [
                'total_land' => '—',
                'formula_values' => [],
            ];
        }

        $calculator = SaleExemptionFileCalculator::calculate($totalMarla, $config);
        $formulaValues = [];
        foreach ($calculator['rows'] as $calcRow) {
            $key = self::plotKey($calcRow['component_slug'], $calcRow['plot_slug']);
            $formulaValues[$key] = [
                'display' => SaleExemptionFileCalculator::formatFileCount($calcRow['file_count']),
                'breakdown' => SaleExemptionFileCalculator::formatFileBreakdown(
                    $calcRow['full_files'],
                    $calcRow['fraction_files'],
                    $calcRow['fraction_marla'],
                ),
            ];
        }

        return [
            'total_land' => LandMeasure::formatAkmsLabelFromMarla($totalMarla),
            'formula_values' => $formulaValues,
        ];
    }

    /**
     * @return list<array{plot_key: string, code: string, short_label: string, plot_label: string, component_label: string, component_slug: string, plot_slug: string}>
     */
    private static function formulaColumnsFromConfig(SaleExemptionConfig $config): array
    {
        $columns = [];
        $globalCodeIndex = 0;

        foreach ($config->components() as $component) {
            foreach ($component->plotTypes as $plot) {
                $globalCodeIndex++;
                $columns[] = [
                    'plot_key' => self::plotKey($component->slug, $plot->slug),
                    'code' => strtoupper(substr($component->slug, 0, 1)).'.'.$globalCodeIndex,
                    'short_label' => self::shortPlotHeader($plot->label, $component->label),
                    'plot_label' => $plot->label,
                    'component_label' => $component->label,
                    'component_slug' => $component->slug,
                    'plot_slug' => $plot->slug,
                ];
            }
        }

        return $columns;
    }

    private static function shortPlotHeader(string $plotLabel, string $componentLabel): string
    {
        if (preg_match('/^(\d+)\s*(Kanal|Marla)/i', $plotLabel, $matches)) {
            $unit = strtoupper(substr($matches[2], 0, 1));

            return $matches[1].$unit.' ('.$componentLabel.')';
        }

        return $plotLabel.' ('.$componentLabel.')';
    }

    /**
     * Sale-land listing: formula file parents → Mouza child groups → seller rows.
     * Only includes purchase files already marked as sale land.
     *
     * @return list<array{
     *   plot_key: string,
     *   code: string,
     *   plot_label: string,
     *   component_label: string,
     *   component_slug: string,
     *   plot_slug: string,
     *   moza_groups: list<array{
     *     moza: string,
     *     purchase_file_id: int,
     *     land_purchase_name: string,
     *     moza_land_area: string,
     *     moza_land_area_marla: float,
     *     formula_files: string,
     *     formula_files_breakdown: string,
     *     rows: list<array<string, mixed>>
     *   }>
     * }>
     */
    public static function formulaGroupedForProject(Project $project): array
    {
        $config = SaleExemptionConfig::forProject($project);

        $files = $project->purchaseFiles()
            ->whereNotNull('sale_land_at')
            ->orderBy('file_name')
            ->with(['purchaseItems' => fn ($q) => $q->with('party')])
            ->get();

        $formulaGroups = [];
        $plotIndex = [];
        $globalCodeIndex = 0;

        foreach ($config->components() as $component) {
            foreach ($component->plotTypes as $plot) {
                $globalCodeIndex++;
                $key = self::plotKey($component->slug, $plot->slug);
                $plotIndex[$key] = count($formulaGroups);
                $formulaGroups[] = [
                    'plot_key' => $key,
                    'code' => strtoupper(substr($component->slug, 0, 1)).'.'.$globalCodeIndex,
                    'plot_label' => $plot->label,
                    'component_label' => $component->label,
                    'component_slug' => $component->slug,
                    'plot_slug' => $plot->slug,
                    'moza_groups' => [],
                ];
            }
        }

        foreach ($files as $file) {
            self::attachFileMozaBlocks($formulaGroups, $plotIndex, $file, $project, $config);
        }

        return self::finalizeFormulaGroups($formulaGroups);
    }

    /**
     * One purchase file's seller lines grouped by Mouza (confirmation modal).
     *
     * @return array{
     *   file_name: string,
     *   project_name: string,
     *   file_date: string,
     *   total_land_area: string,
     *   total_land_area_marla: float,
     *   total_rs: float,
     *   moza_groups: list<array<string, mixed>>
     * }
     */
    public static function mozaGroupsForFile(PurchaseFile $file): array
    {
        $file->loadMissing(['project', 'purchaseItems.party']);

        $mozaGroups = [];
        $totalMarla = 0.0;
        $totalRs = 0.0;

        foreach (self::groupItemsByMoza($file->purchaseItems) as $mozaKey => $items) {
            $sellers = [];
            $mozaMarla = 0.0;
            $mozaRs = 0.0;

            foreach ($items as $item) {
                $itemMarla = (float) $item->land_area_marla;
                if ($itemMarla <= 0) {
                    continue;
                }

                $itemRs = (float) $item->line_total_rs;
                $mozaMarla += $itemMarla;
                $mozaRs += $itemRs;

                $sellers[] = [
                    'owner_name' => $item->party?->name ?: '—',
                    'khasra' => trim((string) ($item->khasra ?? '')) ?: '—',
                    'land_area' => LandMeasure::formatAkmsLabelFromMarla($itemMarla),
                    'land_area_marla' => $itemMarla,
                    'line_total_rs' => $itemRs,
                ];
            }

            if ($sellers === []) {
                continue;
            }

            $totalMarla += $mozaMarla;
            $totalRs += $mozaRs;

            $mozaGroups[] = [
                'moza' => $mozaKey !== '' ? $mozaKey : '—',
                'land_area' => LandMeasure::formatAkmsLabelFromMarla($mozaMarla),
                'land_area_marla' => $mozaMarla,
                'line_total_rs' => $mozaRs,
                'sellers' => $sellers,
            ];
        }

        return [
            'file_name' => $file->file_name,
            'project_name' => $file->project?->name ?? '—',
            'file_date' => $file->file_date?->format('d M Y') ?? '—',
            'total_land_area' => LandMeasure::formatAkmsLabelFromMarla($totalMarla),
            'total_land_area_marla' => $totalMarla,
            'total_rs' => $totalRs,
            'moza_groups' => $mozaGroups,
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $formulaGroups
     * @param  array<string, int>  $plotIndex
     */
    private static function attachFileMozaBlocks(
        array &$formulaGroups,
        array $plotIndex,
        PurchaseFile $file,
        Project $project,
        SaleExemptionConfig $config,
    ): void {
        foreach (self::groupItemsByMoza($file->purchaseItems) as $mozaKey => $items) {
            $mozaMarla = (float) $items->sum(fn (PurchaseItem $item) => (float) $item->land_area_marla);
            if ($mozaMarla <= 0) {
                continue;
            }

            $mozaLabel = $mozaKey !== '' ? $mozaKey : '—';
            $sellers = self::sellerRowsForItems($items, $file, $project, $mozaLabel);
            if ($sellers === []) {
                continue;
            }

            $calculator = SaleExemptionFileCalculator::calculate($mozaMarla, $config);
            $mozaGroupKey = $file->id.'|'.$mozaKey;

            foreach ($calculator['rows'] as $calcRow) {
                $key = self::plotKey($calcRow['component_slug'], $calcRow['plot_slug']);
                if (! isset($plotIndex[$key])) {
                    continue;
                }

                $groupIdx = $plotIndex[$key];
                $formulaFiles = SaleExemptionFileCalculator::formatFileCount($calcRow['file_count']);
                $formulaBreakdown = SaleExemptionFileCalculator::formatFileBreakdown(
                    $calcRow['full_files'],
                    $calcRow['fraction_files'],
                    $calcRow['fraction_marla'],
                );

                $sellerRows = array_map(
                    fn (array $seller) => array_merge($seller, [
                        'formula_files' => $formulaFiles,
                        'formula_files_breakdown' => $formulaBreakdown,
                    ]),
                    $sellers,
                );

                $mozaGroupIdx = self::findMozaGroupIndex($formulaGroups[$groupIdx]['moza_groups'], $mozaGroupKey);

                if ($mozaGroupIdx === null) {
                    $formulaGroups[$groupIdx]['moza_groups'][] = [
                        'moza_group_key' => $mozaGroupKey,
                        'moza' => $mozaLabel,
                        'purchase_file_id' => $file->id,
                        'land_purchase_name' => $file->file_name,
                        'moza_land_area' => LandMeasure::formatAkmsLabelFromMarla($mozaMarla),
                        'moza_land_area_marla' => $mozaMarla,
                        'formula_files' => $formulaFiles,
                        'formula_files_breakdown' => $formulaBreakdown,
                        'rows' => $sellerRows,
                    ];
                } else {
                    $formulaGroups[$groupIdx]['moza_groups'][$mozaGroupIdx]['rows'] = array_merge(
                        $formulaGroups[$groupIdx]['moza_groups'][$mozaGroupIdx]['rows'],
                        $sellerRows,
                    );
                }
            }
        }
    }

    /**
     * @param  Collection<int, PurchaseItem>  $items
     * @return list<array<string, mixed>>
     */
    private static function sellerRowsForItems(
        Collection $items,
        PurchaseFile $file,
        Project $project,
        string $mozaLabel,
    ): array {
        $rows = [];

        foreach ($items as $item) {
            $itemMarla = (float) $item->land_area_marla;
            if ($itemMarla <= 0) {
                continue;
            }

            $rows[] = [
                'land_provider' => $file->file_name,
                'transfer_from' => $item->party?->name ?: '—',
                'transfer_to' => $project->name,
                'moza' => $mozaLabel,
                'khasra' => trim((string) ($item->khasra ?? '')) ?: '—',
                'land_area' => LandMeasure::formatAkmsLabelFromMarla($itemMarla),
                'land_area_marla' => $itemMarla,
            ];
        }

        return $rows;
    }

    /**
     * @param  list<array<string, mixed>>  $mozaGroups
     */
    private static function findMozaGroupIndex(array $mozaGroups, string $mozaGroupKey): ?int
    {
        foreach ($mozaGroups as $index => $group) {
            if (($group['moza_group_key'] ?? '') === $mozaGroupKey) {
                return $index;
            }
        }

        return null;
    }

    /**
     * @param  list<array<string, mixed>>  $formulaGroups
     * @return list<array<string, mixed>>
     */
    private static function finalizeFormulaGroups(array $formulaGroups): array
    {
        $result = [];

        foreach ($formulaGroups as $group) {
            if ($group['moza_groups'] === []) {
                continue;
            }

            foreach ($group['moza_groups'] as &$mozaGroup) {
                $sr = 1;
                foreach ($mozaGroup['rows'] as &$row) {
                    $row['sr'] = $sr++;
                }
                unset($row);
            }
            unset($mozaGroup);

            $result[] = $group;
        }

        return $result;
    }

    private static function plotKey(string $componentSlug, string $plotSlug): string
    {
        return $componentSlug.':'.$plotSlug;
    }

    /**
     * @param  Collection<int, PurchaseItem>  $items
     * @return Collection<string, Collection<int, PurchaseItem>>
     */
    private static function groupItemsByMoza(Collection $items): Collection
    {
        return $items->groupBy(fn (PurchaseItem $item) => trim((string) ($item->moza ?? '')));
    }
}
