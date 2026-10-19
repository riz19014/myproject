<?php

namespace App\Support;

use App\Models\FileSaleRecord;
use App\Models\Project;
use App\Models\PurchaseFile;
use App\Models\Sale;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class FileSaleRecordService
{
    /**
     * Build wizard payload for a purchase file (owners, land records, formula plot options).
     *
     * @return array<string, mixed>
     */
    public function buildFileDetails(Project $project, PurchaseFile $purchaseFile): array
    {
        ProjectExemptionDefaults::ensureForProject($project);
        $purchaseFile->loadMissing(['purchaseItems.party', 'fileSaleLand.collective', 'sales']);
        $config = $this->exemptionConfigForPurchaseFile($project, $purchaseFile);

        $sheet = SaleLandMozaGroups::spreadsheetForProject($project, [$purchaseFile->id], $config);
        $rows = collect($sheet['rows'] ?? []);
        $formulaColumns = $sheet['formula_columns'] ?? [];

        $items = $purchaseFile->purchaseItems;
        $moza = $this->uniqueJoined($items->pluck('moza'));
        $khasra = $this->uniqueJoined($items->pluck('khasra'));
        $khewat = $this->uniqueJoined($items->pluck('khewat_no'));
        $khatooni = $this->uniqueJoined($items->pluck('khatooni_no'));
        $owners = $items->map(fn ($item) => $item->party?->name)->filter()->unique()->values();

        $landOwner = $owners->isEmpty() ? '—' : $owners->implode(', ');
        if ($rows->isNotEmpty()) {
            $rowOwners = $rows->pluck('land_owner')->filter(fn ($v) => filled($v) && $v !== '—')->unique()->values();
            if ($rowOwners->isNotEmpty()) {
                $landOwner = $rowOwners->implode(', ');
            }
        }

        $landProvider = $purchaseFile->file_name;
        if ($rows->isNotEmpty()) {
            $providers = $rows->pluck('land_provider')->filter(fn ($v) => filled($v))->unique()->values();
            if ($providers->isNotEmpty()) {
                $landProvider = $providers->implode(', ');
            }
        }

        $totalMarla = round((float) $items->sum('land_area_marla'), 6);
        if ($rows->isNotEmpty()) {
            $totalMarla = round((float) $rows->sum('total_land_marla'), 6);
        }

        $usedSales = Sale::query()
            ->where('purchase_file_id', $purchaseFile->id)
            ->where('sale_type', Sale::TYPE_SALE_LAND)
            ->get();

        $calc = $totalMarla > 0
            ? SaleExemptionFileCalculator::calculate($totalMarla, $config)
            : ['rows' => [], 'acres' => 0.0, 'marla_per_acre' => $config->marlaPerAcreLand(), 'total_marla' => 0.0];

        if ($rows->isNotEmpty() && $formulaColumns !== []) {
            $plotOptions = $this->plotOptionsFromSheet($formulaColumns, $rows, $usedSales, $config, $calc);
        } else {
            $plotOptions = $this->plotOptionsFromFileMarla($config, $usedSales, $totalMarla, $calc);
        }

        $collective = $purchaseFile->fileSaleLand?->collective;
        $exemptionPayload = is_array($collective?->exemption_payload) ? $collective->exemption_payload : [];
        $hasAppliedPayload = ($exemptionPayload['components'] ?? []) !== [];
        $exemptionSummary = $hasAppliedPayload
            ? $collective->exemptionSummaryLabel()
            : $this->liveExemptionSummary($config);

        return [
            'purchase_file_id' => $purchaseFile->id,
            'project_id' => $project->id,
            'file_name' => $purchaseFile->file_name,
            'is_file_sale' => $purchaseFile->isMovedToFileSale(),
            'is_sale_land' => $purchaseFile->isSaleLand(),
            'land_owner' => $landOwner,
            'land_provider' => $landProvider,
            'moza' => $moza,
            'khasra' => $khasra,
            'khewat_no' => $khewat,
            'khatooni_no' => $khatooni,
            'total_land_marla' => $totalMarla,
            'total_land' => $totalMarla > 0 ? LandMeasure::formatAkmsLabelFromMarla($totalMarla) : '—',
            'remaining_land_marla' => $purchaseFile->remainingLandMarla(),
            'remaining_land' => LandMeasure::formatAkmsLabelFromMarla($purchaseFile->remainingLandMarla()),
            'plot_options' => $plotOptions,
            'mouza_keys' => $rows->pluck('moza_key')->filter()->unique()->values()->all(),
            'exemption' => [
                'summary' => $exemptionSummary,
                'source' => $hasAppliedPayload
                    ? (string) ($collective->name ?? 'Sale file')
                    : 'Live project setup',
                'source_type' => $hasAppliedPayload ? 'collective' : 'live',
                'acres' => (float) ($calc['acres'] ?? 0),
                'acres_display' => SaleExemptionFileCalculator::formatFileCount((float) ($calc['acres'] ?? 0)),
                'marla_per_acre' => (float) ($calc['marla_per_acre'] ?? $config->marlaPerAcreLand()),
                'total_land' => $totalMarla > 0 ? LandMeasure::formatAkmsLabelFromMarla($totalMarla) : '—',
            ],
        ];
    }

    /**
     * Prefer the sale-file collective's applied exemption; fall back to live project setup.
     */
    private function exemptionConfigForPurchaseFile(Project $project, PurchaseFile $purchaseFile): SaleExemptionConfig
    {
        $payload = $purchaseFile->fileSaleLand?->collective?->exemption_payload;
        if (is_array($payload) && ($payload['components'] ?? []) !== []) {
            return SaleExemptionConfig::fromSnapshotData($project, $payload);
        }

        return SaleExemptionConfig::forProject($project);
    }

    private function liveExemptionSummary(SaleExemptionConfig $config): string
    {
        $parts = [];
        foreach ($config->components() as $component) {
            $label = trim((string) $component->label);
            $pct = rtrim(rtrim(number_format((float) $component->pool_percent, 4, '.', ''), '0'), '.');
            $parts[] = ($label !== '' ? $label : $component->slug).' '.$pct.'%';
        }

        return $parts !== [] ? implode(' · ', $parts) : '—';
    }

    /**
     * @param  array<string, mixed>  $calc
     * @param  array<string, mixed>  $base
     * @return array<string, mixed>
     */
    private function enrichPlotOption(array $base, array $calc, float $available, int $usedQty, float $remaining): array
    {
        $calcRow = collect($calc['rows'] ?? [])->first(
            fn (array $row) => ($row['component_slug'] ?? '') === ($base['component_slug'] ?? '')
                && ($row['plot_slug'] ?? '') === ($base['plot_slug'] ?? '')
        ) ?: [];

        $marlaPerPlot = (float) ($calcRow['marla_per_plot'] ?? $base['marla_per_plot'] ?? 0);
        $nominal = (float) ($calcRow['nominal_marla'] ?? $base['nominal_marla'] ?? 0);
        $acres = (float) ($calc['acres'] ?? 0);
        $product = (float) ($calcRow['product_marla'] ?? round($marlaPerPlot * $acres, 4));
        $share = (float) ($calcRow['share_percent'] ?? 0);

        $calculation = SaleExemptionFileCalculator::formatMarlaWithUnit($marlaPerPlot)
            .' × '.SaleExemptionFileCalculator::formatFileCount($acres)
            .' = '.SaleExemptionFileCalculator::formatMarlaWithUnit($product)
            .' ÷ '.SaleExemptionFileCalculator::formatMarlaWithUnit($nominal);

        return array_merge($base, [
            'marla_per_plot' => $marlaPerPlot,
            'nominal_marla' => $nominal,
            'share_percent' => $share,
            'share_display' => rtrim(rtrim(number_format($share, 4, '.', ''), '0'), '.').'%',
            'calculation_display' => $calculation,
            'available_files' => round($available, 4),
            'available_display' => SaleExemptionFileCalculator::formatFileCount($available),
            'remaining_files' => round($remaining, 4),
            'remaining_display' => SaleExemptionFileCalculator::formatFileCount($remaining),
            'used_quantity' => $usedQty,
            'sold_display' => $usedQty > 0 ? (string) $usedQty : '—',
            'disabled' => $remaining <= 1e-6,
        ]);
    }

    /**
     * @param  list<array<string, mixed>>  $formulaColumns
     * @param  Collection<int, array<string, mixed>>  $rows
     * @param  Collection<int, Sale>  $usedSales
     * @return list<array<string, mixed>>
     */
    /**
     * @param  array<string, mixed>  $calc
     * @return list<array<string, mixed>>
     */
    private function plotOptionsFromSheet(
        array $formulaColumns,
        Collection $rows,
        Collection $usedSales,
        SaleExemptionConfig $config,
        array $calc = [],
    ): array {
        $plotOptions = [];
        foreach ($formulaColumns as $column) {
            $plotKey = $column['plot_key'];
            $available = (float) $rows->sum(
                fn (array $row) => (float) ($row['formula_values'][$plotKey]['file_count'] ?? 0)
            );
            $usedQty = (int) $usedSales
                ->where('component', $column['component_slug'])
                ->where('plot_type', $column['plot_slug'])
                ->sum('plot_quantity');
            $remaining = max(0, $available - $usedQty);

            $plotOptions[] = $this->enrichPlotOption([
                'plot_key' => $plotKey,
                'code' => $column['code'] ?? '',
                'label' => $column['short_label'] ?? $column['plot_label'],
                'plot_label' => $column['plot_label'],
                'component_label' => $column['component_label'],
                'component_slug' => $column['component_slug'],
                'plot_slug' => $column['plot_slug'],
                'marla_per_plot' => $config->plotMarla($column['component_slug'], $column['plot_slug']),
            ], $calc, $available, $usedQty, $remaining);
        }

        return $plotOptions;
    }

    /**
     * @param  Collection<int, Sale>  $usedSales
     * @param  array<string, mixed>  $calc
     * @return list<array<string, mixed>>
     */
    private function plotOptionsFromFileMarla(
        SaleExemptionConfig $config,
        Collection $usedSales,
        float $totalMarla,
        array $calc = [],
    ): array {
        if ($totalMarla <= 0) {
            return [];
        }

        if (($calc['rows'] ?? []) === []) {
            $calc = SaleExemptionFileCalculator::calculate($totalMarla, $config);
        }
        $options = [];

        foreach ($calc['rows'] as $row) {
            $component = (string) $row['component_slug'];
            $plotSlug = (string) $row['plot_slug'];
            $available = (float) ($row['file_count'] ?? 0);
            $usedQty = (int) $usedSales
                ->where('component', $component)
                ->where('plot_type', $plotSlug)
                ->sum('plot_quantity');
            $remaining = max(0, $available - $usedQty);

            $options[] = $this->enrichPlotOption([
                'plot_key' => $component.'.'.$plotSlug,
                'code' => (string) ($row['code'] ?? ''),
                'label' => (string) ($row['plot_label'] ?? $plotSlug),
                'plot_label' => (string) ($row['plot_label'] ?? $plotSlug),
                'component_label' => (string) ($row['component_label'] ?? $component),
                'component_slug' => $component,
                'plot_slug' => $plotSlug,
                'marla_per_plot' => (float) ($row['marla_per_plot'] ?? $config->plotMarla($component, $plotSlug)),
                'nominal_marla' => (float) ($row['nominal_marla'] ?? 0),
            ], $calc, $available, $usedQty, $remaining);
        }

        return $options;
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  list<UploadedFile>  $documents
     */
    public function store(Project $project, PurchaseFile $purchaseFile, array $data, array $documents = []): FileSaleRecord
    {
        ProjectExemptionDefaults::ensureForProject($project);
        $purchaseFile->loadMissing('fileSaleLand.collective');
        $config = $this->exemptionConfigForPurchaseFile($project, $purchaseFile);
        $details = $this->buildFileDetails($project, $purchaseFile);

        $component = (string) $data['component'];
        $plotType = (string) $data['plot_type'];
        $qty = max(1, (int) $data['plot_quantity']);

        $option = collect($details['plot_options'])
            ->first(fn (array $o) => $o['component_slug'] === $component && $o['plot_slug'] === $plotType);

        if (! $option) {
            throw ValidationException::withMessages([
                'plot_type' => ['Invalid plot type for this file.'],
            ]);
        }

        if ($qty > ((float) $option['remaining_files']) + 0.0001) {
            throw ValidationException::withMessages([
                'plot_quantity' => [
                    'Only '.$option['remaining_display'].' available for '.$option['label'].'.',
                ],
            ]);
        }

        $marlaPerPlot = (float) ($option['marla_per_plot'] ?? $config->plotMarla($component, $plotType));
        $totalMarla = round($marlaPerPlot * $qty, 4);
        $mozaKeys = $details['mouza_keys'] ?? [];
        $status = (string) ($data['status'] ?? FileSaleRecord::STATUS_COMPLETE);
        if (! in_array($status, [
            FileSaleRecord::STATUS_PENDING,
            FileSaleRecord::STATUS_COMPLETE,
            FileSaleRecord::STATUS_CANCELLED,
        ], true)) {
            $status = FileSaleRecord::STATUS_COMPLETE;
        }

        return DB::transaction(function () use (
            $project,
            $purchaseFile,
            $data,
            $documents,
            $component,
            $plotType,
            $qty,
            $totalMarla,
            $mozaKeys,
            $details,
            $status
        ) {
            $sale = null;
            if ($status !== FileSaleRecord::STATUS_CANCELLED) {
                $sale = Sale::create([
                    'project_id' => $project->id,
                    'purchase_file_id' => $purchaseFile->id,
                    'sale_land_moza_keys' => $mozaKeys !== [] ? $mozaKeys : null,
                    'sale_type' => Sale::TYPE_SALE_LAND,
                    'component' => $component,
                    'plot_type' => $plotType,
                    'plot_quantity' => $qty,
                    'customer_id' => null,
                    'area_acre' => 0,
                    'area_kanal' => 0,
                    'area_marla' => 0,
                    'area_sqft' => 0,
                    'land_area_marla' => $totalMarla,
                    'total_amount' => round((float) ($data['total_amount'] ?? 0), 2),
                ]);
            }

            $landOwner = array_key_exists('land_owner', $data)
                ? trim((string) $data['land_owner'])
                : (string) ($details['land_owner'] ?? '');
            $landProvider = array_key_exists('land_provider', $data)
                ? trim((string) $data['land_provider'])
                : (string) ($details['land_provider'] ?? '');
            if ($landOwner === '—' || $landOwner === '') {
                $landOwner = '';
            }
            if ($landProvider === '—' || $landProvider === '') {
                $landProvider = '';
            }

            $record = FileSaleRecord::create([
                'project_id' => $project->id,
                'purchase_file_id' => $purchaseFile->id,
                'sale_id' => $sale?->id,
                'e_stamp_id' => trim((string) $data['e_stamp_id']),
                'land_owner' => $landOwner !== '' ? $landOwner : null,
                'land_provider' => $landProvider !== '' ? $landProvider : null,
                'purchaser_name' => trim((string) $data['purchaser_name']),
                'moza' => $details['moza'] !== '—' ? $details['moza'] : null,
                'khasra' => $details['khasra'] !== '—' ? $details['khasra'] : null,
                'khewat_no' => $details['khewat_no'] !== '—' ? $details['khewat_no'] : null,
                'khatooni_no' => $details['khatooni_no'] !== '—' ? $details['khatooni_no'] : null,
                'component' => $component,
                'plot_type' => $plotType,
                'plot_quantity' => $qty,
                'land_area_marla' => $totalMarla,
                'total_amount' => round((float) ($data['total_amount'] ?? 0), 2),
                'status' => $status,
                'notes' => isset($data['notes']) ? trim((string) $data['notes']) : null,
            ]);

            foreach ($documents as $index => $file) {
                if (! $file instanceof UploadedFile) {
                    continue;
                }
                if (! $file->isValid()) {
                    throw ValidationException::withMessages([
                        'documents.'.$index => [$file->getErrorMessage() ?: 'Document upload failed.'],
                    ]);
                }
                $record->addDocument($file);
            }

            return $record->load(['documents', 'purchaseFile.project', 'sale']);
        });
    }

    private function uniqueJoined(Collection $values): string
    {
        $clean = $values->filter(fn ($v) => filled($v))->map(fn ($v) => trim((string) $v))->unique()->values();

        return $clean->isEmpty() ? '—' : $clean->implode(', ');
    }
}
