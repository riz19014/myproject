<?php

namespace App\Http\Controllers;

use App\Models\PartyCategory;
use Illuminate\Http\Request;

class PartyCategoryController extends Controller
{
    public function index()
    {
        $partyCategories = PartyCategory::orderBy('id', 'desc')->paginate(10);

        return view('party-categories.index', compact('partyCategories'));
    }

    public function create()
    {
        return view('party-categories.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        PartyCategory::create($validated);

        return redirect()->route('party-categories.index')
            ->with('success', 'Party category created successfully.');
    }

    public function edit(PartyCategory $party_category)
    {
        return view('party-categories.edit', ['partyCategory' => $party_category]);
    }

    public function update(Request $request, PartyCategory $party_category)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $party_category->update($validated);

        return redirect()->route('party-categories.index')
            ->with('success', 'Party category updated successfully.');
    }

    public function destroy(PartyCategory $party_category)
    {
        $party_category->delete();

        return redirect()->route('party-categories.index')
            ->with('success', 'Party category deleted successfully.');
    }
}
