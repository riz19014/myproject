<?php

namespace App\Http\Controllers;

use App\Models\DayBookEntry;
use App\Models\FileSaleRecord;
use App\Models\FileSaleRecordDocument;
use App\Models\Land;
use App\Models\Plot;
use App\Models\PurchaseFile;
use App\Support\LandMeasure;
use App\Support\ProjectExemptionDefaults;
use App\Support\SaleExemptionConfig;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LandController extends Controller
{
    public function index()
    {
        $lands = Land::withCount('plots')->orderBy('id', 'desc')->paginate(10);

        $rawRecords = FileSaleRecord::query()
            ->with([
                'project.landType',
                'purchaseFile.purchaseItems',
                'purchaseFile.sales',
                'purchaseFile.project.landType',
                'documents',
                'sale',
            ])
            ->orderByDesc('id')
            ->get();

        $configByProject = [];
        $fileSaleRecords = $rawRecords->map(function (FileSaleRecord $record) use (&$configByProject) {
            $file = $record->purchaseFile;
            $project = $record->project ?? $file?->project;
            $marla = (float) $record->land_area_marla;
            $plotLabel = '—';
            if ($project && $record->component && $record->plot_type) {
                $projectId = (int) $project->id;
                if (! isset($configByProject[$projectId])) {
                    ProjectExemptionDefaults::ensureForProject($project);
                    $configByProject[$projectId] = SaleExemptionConfig::forProject($project);
                }
                /** @var SaleExemptionConfig $config */
                $config = $configByProject[$projectId];
                $label = $config->plotLabel((string) $record->component, (string) $record->plot_type);
                $qty = (int) $record->plot_quantity;
                $plotLabel = $label.($qty > 1 ? ' × '.$qty : '');
            }

            $totalMarla = $file ? $file->totalLandMarla() : 0.0;
            $soldMarla = $file ? $file->soldLandMarla() : 0.0;
            $remainingMarla = $file ? $file->remainingLandMarla() : 0.0;

            return [
                'id' => $record->id,
                'e_stamp_id' => $record->e_stamp_id,
                'purchaser_name' => $record->purchaser_name,
                'land_owner' => $record->land_owner ?: '—',
                'land_provider' => $record->land_provider ?: '—',
                'moza' => $record->moza ?: '—',
                'khasra' => $record->khasra ?: '—',
                'khewat_no' => $record->khewat_no ?: '—',
                'khatooni_no' => $record->khatooni_no ?: '—',
                'plot_label' => $plotLabel,
                'component' => $record->component ?: '—',
                'plot_type' => $record->plot_type ?: '—',
                'plot_quantity' => (int) $record->plot_quantity,
                'land_area_marla' => $marla,
                'land_area_label' => $marla > 0 ? LandMeasure::formatAkmsLabelFromMarla($marla) : '—',
                'amount' => (float) ($record->total_amount ?? 0),
                'amount_formatted' => $record->total_amount !== null
                    ? 'Rs '.number_format((float) $record->total_amount, 0)
                    : '—',
                'status' => $record->status,
                'status_label' => $record->statusLabel(),
                'file_name' => $file?->file_name ?? ('File #'.$record->purchase_file_id),
                'purchase_file_id' => $record->purchase_file_id,
                'project_id' => $project?->id,
                'project_name' => $project?->name ?? '—',
                'project_is_dha' => $project?->isDha() ?? false,
                'created_at' => $record->created_at?->format('d M Y H:i') ?? '—',
                'notes' => $record->notes ?: '—',
                'file_total_label' => $totalMarla > 0 ? LandMeasure::formatAkmsLabelFromMarla($totalMarla) : '—',
                'file_sold_label' => $soldMarla > 0 ? LandMeasure::formatAkmsLabelFromMarla($soldMarla) : '—',
                'file_remaining_label' => $totalMarla > 0 ? LandMeasure::formatAkmsLabelFromMarla($remainingMarla) : '—',
                'file_status' => $file?->saleStatusLabel() ?? '—',
                'documents' => $record->documents->map(fn ($doc) => [
                    'id' => $doc->id,
                    'name' => $doc->name ?: 'Document',
                    'url' => route('sale-land.documents.show', [
                        'record' => $record->id,
                        'document' => $doc->id,
                    ], false),
                ])->values()->all(),
            ];
        });

        $activeRecords = $fileSaleRecords->where('status', '!=', FileSaleRecord::STATUS_CANCELLED);
        $areaSum = round((float) $activeRecords->sum('land_area_marla'), 6);
        $fileSaleRecordSummary = [
            'records_count' => $fileSaleRecords->count(),
            'complete_count' => $fileSaleRecords->where('status', FileSaleRecord::STATUS_COMPLETE)->count(),
            'pending_count' => $fileSaleRecords->where('status', FileSaleRecord::STATUS_PENDING)->count(),
            'total_amount_formatted' => 'Rs '.number_format((float) $activeRecords->sum('amount'), 0),
            'total_sold_label' => $areaSum > 0
                ? LandMeasure::formatAkmsLabelFromMarla($areaSum)
                : '—',
        ];

        $purchaseFileIds = $rawRecords->pluck('purchase_file_id')->unique()->filter()->values()->all();
        $inventoryFiles = $purchaseFileIds === []
            ? collect()
            : PurchaseFile::query()
                ->whereIn('id', $purchaseFileIds)
                ->with(['project.landType', 'purchaseItems', 'sales'])
                ->orderBy('file_name')
                ->get()
                ->map(function (PurchaseFile $file) {
                    $total = $file->totalLandMarla();
                    $sold = $file->soldLandMarla();
                    $remaining = $file->remainingLandMarla();
                    $project = $file->project;

                    return [
                        'purchase_file_id' => $file->id,
                        'file_name' => $file->file_name,
                        'project_id' => $project?->id,
                        'project_name' => $project?->name ?? '—',
                        'project_is_dha' => $project?->isDha() ?? false,
                        'total_label' => $total > 0 ? LandMeasure::formatAkmsLabelFromMarla($total) : '—',
                        'sold_label' => $sold > 0 ? LandMeasure::formatAkmsLabelFromMarla($sold) : '—',
                        'remaining_label' => $total > 0 ? LandMeasure::formatAkmsLabelFromMarla($remaining) : '—',
                        'status' => $file->saleStatusLabel(),
                        'sales_count' => $file->sales->count(),
                        'file_sale_url' => $project ? route('sale.files.index', $project) : null,
                    ];
                });

        $fileSaleSoldEntries = DayBookEntry::query()
            ->whereNotNull('purchase_file_id')
            ->whereNotNull('sold_area_marla')
            ->where('sold_area_marla', '>', 0)
            ->with([
                'purchaseFile.project',
                'purchaseFile.purchaseItems',
                'project',
                'partySubCategory.category',
                'paidByParty',
            ])
            ->orderByDesc('entry_date')
            ->orderByDesc('id')
            ->get();

        $soldFiles = $this->buildSoldFilesSummary($fileSaleSoldEntries);

        $fileSaleSoldSummary = [
            'files_count' => $soldFiles->count(),
            'entries_count' => $fileSaleSoldEntries->count(),
            'total_sold_marla' => round((float) $fileSaleSoldEntries->sum('sold_area_marla'), 6),
            'total_sold_label' => $fileSaleSoldEntries->isEmpty()
                ? '—'
                : LandMeasure::formatAkmsLabelFromMarla((float) $fileSaleSoldEntries->sum('sold_area_marla')),
            'total_amount' => round((float) $fileSaleSoldEntries->sum('amount'), 2),
            'total_amount_formatted' => 'Rs '.number_format((float) $fileSaleSoldEntries->sum('amount'), 0),
        ];

        return view('lands.index', compact(
            'lands',
            'soldFiles',
            'fileSaleSoldEntries',
            'fileSaleSoldSummary',
            'fileSaleRecords',
            'fileSaleRecordSummary',
            'inventoryFiles'
        ));
    }

    public function create()
    {
        return view('lands.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'total_area_kanal' => ['nullable', 'numeric', 'min:0'],
            'location' => ['nullable', 'string', 'max:255'],
            'purchase_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
        ]);
        Land::create($validated);

        return redirect()->route('sale-land.index')->with('success', 'Land recorded successfully.');
    }

    public function show(Land $land)
    {
        $land->load(['plots.customer', 'plots.documents']);
        $paymentsLand = DayBookEntry::where('link_type', 'land')->where('link_id', $land->id)->orderBy('entry_date', 'desc')->get();

        return view('lands.show', compact('land', 'paymentsLand'));
    }

    public function edit(Land $land)
    {
        return view('lands.edit', compact('land'));
    }

    public function update(Request $request, Land $land)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'total_area_kanal' => ['nullable', 'numeric', 'min:0'],
            'location' => ['nullable', 'string', 'max:255'],
            'purchase_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
        ]);
        $land->update($validated);

        return redirect()->route('sale-land.index')->with('success', 'Land updated successfully.');
    }

    public function destroy(Land $land)
    {
        $land->delete();

        return redirect()->route('sale-land.index')->with('success', 'Land deleted successfully.');
    }

    public function addPlot(Request $request, Land $land)
    {
        $validated = $request->validate([
            'plot_number' => ['required', 'string', 'max:100'],
            'size' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string'],
        ]);
        $land->plots()->create(array_merge($validated, ['status' => 'available']));

        return redirect()->route('lands.show', $land)->with('success', 'Plot added.');
    }

    public function sellPlot(Request $request, Land $land, Plot $plot)
    {
        $validated = $request->validate([
            'customer_id' => ['required', 'exists:customers,id'],
            'sale_amount' => ['nullable', 'numeric', 'min:0'],
            'sale_date' => ['nullable', 'date'],
        ]);
        $plot->update([
            'status' => 'sold',
            'customer_id' => $validated['customer_id'],
            'sale_amount' => $validated['sale_amount'] ?? null,
            'sale_date' => $validated['sale_date'] ?? now(),
        ]);

        return redirect()->route('lands.show', $land)->with('success', 'Plot marked as sold.');
    }

    public function uploadPlotDocument(Request $request, Land $land, Plot $plot)
    {
        $request->validate(['documents' => 'required', 'documents.*' => 'file|max:10240']);
        foreach ($request->file('documents') as $file) {
            $plot->addDocument($file);
        }

        return redirect()->route('lands.show', $land)->with('success', 'Document(s) uploaded.');
    }

    public function destroyPlotDocument(Land $land, Plot $plot, int $document)
    {
        $doc = $plot->documents()->findOrFail($document);
        $doc->delete();

        return redirect()->route('lands.show', $land)->with('success', 'Document removed.');
    }

    /**
     * View / download a document attached to a file sale record.
     */
    public function showFileSaleDocument(FileSaleRecord $record, FileSaleRecordDocument $document): StreamedResponse
    {
        abort_unless((int) $document->file_sale_record_id === (int) $record->id, 404);
        abort_unless($document->file_path && Storage::disk('public')->exists($document->file_path), 404);

        $filename = $document->name ?: basename($document->file_path);

        return Storage::disk('public')->response(
            $document->file_path,
            $filename,
            [
                'Content-Disposition' => 'inline; filename="'.$filename.'"',
            ]
        );
    }

    /**
     * @param  Collection<int, DayBookEntry>  $entries
     * @return Collection<int, array<string, mixed>>
     */
    private function buildSoldFilesSummary(Collection $entries): Collection
    {
        return $entries
            ->groupBy('purchase_file_id')
            ->map(function (Collection $group) {
                /** @var DayBookEntry $first */
                $first = $group->first();
                /** @var PurchaseFile|null $file */
                $file = $first->purchaseFile;
                $soldMarla = round((float) $group->sum('sold_area_marla'), 6);
                $amount = round((float) $group->sum('amount'), 2);
                $totalMarla = $file ? $file->totalLandMarla() : 0.0;
                $remainingMarla = $file ? max(0.0, round($totalMarla - $file->soldLandMarla(), 6)) : 0.0;
                $project = $file?->project ?? $first->project;
                /** @var DayBookEntry $latest */
                $latest = $group->sortByDesc(fn (DayBookEntry $entry) => $entry->entry_date?->timestamp ?? 0)->first();

                return [
                    'purchase_file_id' => (int) $first->purchase_file_id,
                    'file_name' => $file?->file_name ?? ('File #'.$first->purchase_file_id),
                    'project_id' => $project?->id,
                    'project_name' => $project?->name ?? '—',
                    'project_is_dha' => $project?->isDha() ?? false,
                    'entries_count' => $group->count(),
                    'sold_marla' => $soldMarla,
                    'sold_label' => LandMeasure::formatAkmsLabelFromMarla($soldMarla),
                    'total_label' => $totalMarla > 0 ? LandMeasure::formatAkmsLabelFromMarla($totalMarla) : '—',
                    'remaining_label' => $totalMarla > 0 ? LandMeasure::formatAkmsLabelFromMarla($remainingMarla) : '—',
                    'status' => $file?->saleStatusLabel() ?? '—',
                    'amount' => $amount,
                    'amount_formatted' => 'Rs '.number_format($amount, 0),
                    'last_sale_date' => $latest->entry_date?->format('d M Y') ?? '—',
                    'last_sale_sort' => $latest->entry_date?->timestamp ?? 0,
                    'entries' => $group->map(function (DayBookEntry $entry) {
                        return [
                            'id' => $entry->id,
                            'date' => $entry->entry_date?->format('d M Y') ?? '—',
                            'voucher' => $entry->voucher_no ?: '—',
                            'area' => $entry->getSoldAreaLabel(),
                            'amount' => 'Rs '.number_format((float) $entry->amount, 0),
                            'party' => $entry->partySubCategory?->name ?? '—',
                            'category' => $entry->partySubCategory?->category?->name ?? '—',
                            'paid_by' => $entry->getPaidByLabel(),
                            'description' => $entry->description ?: '—',
                            'url' => route('daybook.show', $entry),
                        ];
                    })->values()->all(),
                ];
            })
            ->sortByDesc('last_sale_sort')
            ->values();
    }
}
