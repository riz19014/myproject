<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\DayBookEntry;
use App\Models\Factory;
use App\Models\Land;
use App\Models\Plot;
use App\Models\Project;
use Illuminate\Http\Request;

class DayBookController extends Controller
{
    public function index(Request $request)
    {
        $query = DayBookEntry::query()->orderBy('entry_date', 'desc')->orderBy('id', 'desc');

        if ($request->filled('from')) {
            $query->where('entry_date', '>=', $request->from);
        }
        if ($request->filled('to')) {
            $query->where('entry_date', '<=', $request->to);
        }
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }
        if ($request->filled('link_type')) {
            $query->where('link_type', $request->link_type);
        }

        $entries = $query->paginate(20);
        $totalIn = DayBookEntry::where('type', 'cash_in')->when($request->filled('from'), fn ($q) => $q->where('entry_date', '>=', $request->from))->when($request->filled('to'), fn ($q) => $q->where('entry_date', '<=', $request->to))->sum('amount');
        $totalOut = DayBookEntry::where('type', 'cash_out')->when($request->filled('from'), fn ($q) => $q->where('entry_date', '>=', $request->from))->when($request->filled('to'), fn ($q) => $q->where('entry_date', '<=', $request->to))->sum('amount');

        return view('daybook.index', compact('entries', 'totalIn', 'totalOut'));
    }

    public function create()
    {
        $projects = Project::orderBy('name')->get();
        $lands = Land::orderBy('name')->get();
        $plots = Plot::with('land')->orderBy('id')->get();
        $factories = Factory::orderBy('name')->get();
        $customers = Customer::orderBy('name')->get();
        return view('daybook.create', compact('projects', 'lands', 'plots', 'factories', 'customers'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'entry_date' => ['required', 'date'],
            'type' => ['required', 'in:cash_in,cash_out'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'description' => ['nullable', 'string'],
            'link_type' => ['nullable', 'in:office,project,land,plot,factory,customer'],
            'link_id' => ['nullable', 'integer', 'min:1'],
        ]);

        if (empty($validated['link_type']) || $validated['link_type'] === 'office') {
            $validated['link_type'] = 'office';
            $validated['link_id'] = null;
        } else {
            if (empty($validated['link_id'])) {
                return back()->withErrors(['link_id' => 'Please select a record to link.'])->withInput();
            }
        }

        DayBookEntry::create($validated);
        return redirect()->route('daybook.index')->with('success', 'DayBook entry added. It is linked to the selected record.');
    }

    public function show(DayBookEntry $entry)
    {
        return view('daybook.show', compact('entry'));
    }

    public function edit(DayBookEntry $entry)
    {
        $projects = Project::orderBy('name')->get();
        $lands = Land::orderBy('name')->get();
        $plots = Plot::with('land')->orderBy('id')->get();
        $factories = Factory::orderBy('name')->get();
        $customers = Customer::orderBy('name')->get();
        return view('daybook.edit', compact('entry', 'projects', 'lands', 'plots', 'factories', 'customers'));
    }

    public function update(Request $request, DayBookEntry $entry)
    {
        $validated = $request->validate([
            'entry_date' => ['required', 'date'],
            'type' => ['required', 'in:cash_in,cash_out'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'description' => ['nullable', 'string'],
            'link_type' => ['nullable', 'in:office,project,land,plot,factory,customer'],
            'link_id' => ['nullable', 'integer', 'min:1'],
        ]);

        if (empty($validated['link_type']) || $validated['link_type'] === 'office') {
            $validated['link_type'] = 'office';
            $validated['link_id'] = null;
        } elseif (empty($validated['link_id'])) {
            return back()->withErrors(['link_id' => 'Please select a record to link.'])->withInput();
        }

        $entry->update($validated);
        return redirect()->route('daybook.index')->with('success', 'Entry updated.');
    }

    public function destroy(DayBookEntry $entry)
    {
        $entry->delete();
        return redirect()->route('daybook.index')->with('success', 'Entry deleted.');
    }
}
