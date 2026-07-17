<?php

namespace App\Http\Controllers;

use App\Models\DayBookEntry;
use App\Models\Land;
use App\Models\Plot;
use App\Models\PurchaseFile;
use App\Support\LandMeasure;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class LandController extends Controller
{
    public function index()
    {
        $lands = Land::withCount('plots')->orderBy('id', 'desc')->paginate(10);

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

        return view('lands.index', compact('lands', 'soldFiles', 'fileSaleSoldEntries', 'fileSaleSoldSummary'));
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
