<?php

namespace App\Http\Controllers;

use App\Models\DayBookEntry;
use App\Models\Factory;
use Illuminate\Http\Request;

class FactoryController extends Controller
{
    public function index()
    {
        $factories = Factory::orderBy('id', 'desc')->paginate(10);
        return view('factories.index', compact('factories'));
    }

    public function create()
    {
        return view('factories.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'purchase_cost' => ['nullable', 'numeric', 'min:0'],
            'purchase_date' => ['nullable', 'date'],
            'location' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ]);
        Factory::create($validated);
        return redirect()->route('factories.index')->with('success', 'Factory recorded successfully.');
    }

    public function show(Factory $factory)
    {
        $expenses = DayBookEntry::where('link_type', 'factory')->where('link_id', $factory->id)->orderBy('entry_date', 'desc')->get();
        return view('factories.show', compact('factory', 'expenses'));
    }

    public function edit(Factory $factory)
    {
        return view('factories.edit', compact('factory'));
    }

    public function update(Request $request, Factory $factory)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'purchase_cost' => ['nullable', 'numeric', 'min:0'],
            'purchase_date' => ['nullable', 'date'],
            'location' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ]);
        $factory->update($validated);
        return redirect()->route('factories.index')->with('success', 'Factory updated successfully.');
    }

    public function destroy(Factory $factory)
    {
        $factory->delete();
        return redirect()->route('factories.index')->with('success', 'Factory deleted successfully.');
    }
}
