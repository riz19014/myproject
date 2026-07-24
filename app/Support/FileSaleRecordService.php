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
        $config = SaleExemptionConfig::forProject($project);

        $sheet = SaleLandMozaGroups::spreadsheetForProject($project, [$purchaseFile->id]);
        $rows = collect($sheet['rows'] ?? []);
        $formulaColumns = $sheet['formula_columns'] ?? [];

        $purchaseFile->loadMissing(['purchaseItems.party', 'fileSaleLand', 'sales']);

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

        if ($rows->isNotEmpty() && $formulaColumns !== []) {
            $plotOptions = $this->plotOptionsFromSheet($formulaColumns, $rows, $usedSales, $config);
        } else {
            $plotOptions = $this->plotOptionsFromFileMarla($config, $usedSales, $totalMarla);
        }

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
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $formulaColumns
     * @param  Collection<int, array<string, mixed>>  $rows
     * @param  Collection<int, Sale>  $usedSales
     * @return list<array<string, mixed>>
     */
    private function plotOptionsFromSheet(array $formulaColumns, Collection $rows, Collection $usedSales, SaleExemptionConfig $config): array
    {
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

            $plotOptions[] = [
                'plot_key' => $plotKey,
                'code' => $column['code'] ?? '',
                'label' => $column['short_label'] ?? $column['plot_label'],
                'plot_label' => $column['plot_label'],
                'component_label' => $column['component_label'],
                'component_slug' => $column['component_slug'],
                'plot_slug' => $column['plot_slug'],
                'marla_per_plot' => $config->plotMarla($column['component_slug'], $column['plot_slug']),
                'available_files' => round($available, 4),
                'available_display' => SaleExemptionFileCalculator::formatFileCount($available),
                'remaining_files' => round($remaining, 4),
                'remaining_display' => SaleExemptionFileCalculator::formatFileCount($remaining),
                'used_quantity' => $usedQty,
                'disabled' => $remaining <= 1e-6,
            ];
        }

        return $plotOptions;
    }

    /**
     * @param  Collection<int, Sale>  $usedSales
     * @return list<array<string, mixed>>
     */
    private function plotOptionsFromFileMarla(SaleExemptionConfig $config, Collection $usedSales, float $totalMarla): array
    {
        if ($totalMarla <= 0) {
            return [];
        }

        $calc = SaleExemptionFileCalculator::calculate($totalMarla, $config);
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

            $options[] = [
                'plot_key' => $component.'.'.$plotSlug,
                'code' => (string) ($row['code'] ?? ''),
                'label' => (string) ($row['plot_label'] ?? $plotSlug),
                'plot_label' => (string) ($row['plot_label'] ?? $plotSlug),
                'component_label' => (string) ($row['component_label'] ?? $component),
                'component_slug' => $component,
                'plot_slug' => $plotSlug,
                'marla_per_plot' => (float) ($row['marla_per_plot'] ?? $config->plotMarla($component, $plotSlug)),
                'available_files' => round($available, 4),
                'available_display' => SaleExemptionFileCalculator::formatFileCount($available),
                'remaining_files' => round($remaining, 4),
                'remaining_display' => SaleExemptionFileCalculator::formatFileCount($remaining),
                'used_quantity' => $usedQty,
                'disabled' => $remaining <= 1e-6,
            ];
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
        $config = SaleExemptionConfig::forProject($project);
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

            $record = FileSaleRecord::create([
                'project_id' => $project->id,
                'purchase_file_id' => $purchaseFile->id,
                'sale_id' => $sale?->id,
                'e_stamp_id' => trim((string) $data['e_stamp_id']),
                'land_owner' => $details['land_owner'] !== '—' ? $details['land_owner'] : null,
                'land_provider' => $details['land_provider'] !== '—' ? $details['land_provider'] : null,
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

            foreach ($documents as $file) {
                if ($file instanceof UploadedFile && $file->isValid()) {
                    $record->addDocument($file);
                }
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
