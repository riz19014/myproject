<?php

namespace App\Http\Controllers;

use App\Models\DayBookEntry;
use App\Models\Land;
use App\Models\Plot;
use Illuminate\Http\Request;

class LandController extends Controller
{
    public function index()
    {
        $lands = Land::withCount('plots')->orderBy('id', 'desc')->paginate(10);
        return view('lands.index', compact('lands'));
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
        return redirect()->route('lands.index')->with('success', 'Land recorded successfully.');
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
        return redirect()->route('lands.index')->with('success', 'Land updated successfully.');
    }

    public function destroy(Land $land)
    {
        $land->delete();
        return redirect()->route('lands.index')->with('success', 'Land deleted successfully.');
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
}
