<?php

namespace App\Http\Controllers;

use App\Models\PartyCategory;
use App\Models\PartySubCategory;
use Illuminate\Http\Request;

class PartySubCategoryController extends Controller
{
    public function index()
    {
        $partySubCategories = PartySubCategory::query()
            ->with('category')
            ->orderBy('id', 'desc')
            ->paginate(10);

        return view('party-sub-categories.index', compact('partySubCategories'));
    }

    public function create()
    {
        $partyCategories = PartyCategory::orderBy('name')->get();

        return view('party-sub-categories.create', compact('partyCategories'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'category_id' => ['required', 'integer', 'exists:party_categories,id'],
            'name' => ['required', 'string', 'max:255'],
        ]);

        PartySubCategory::create($validated);

        return redirect()->route('party-sub-categories.index')
            ->with('success', 'Party sub category created successfully.');
    }

    public function edit(PartySubCategory $party_sub_category)
    {
        $partyCategories = PartyCategory::orderBy('name')->get();

        return view('party-sub-categories.edit', [
            'partySubCategory' => $party_sub_category,
            'partyCategories' => $partyCategories,
        ]);
    }

    public function update(Request $request, PartySubCategory $party_sub_category)
    {
        $validated = $request->validate([
            'category_id' => ['required', 'integer', 'exists:party_categories,id'],
            'name' => ['required', 'string', 'max:255'],
        ]);

        $party_sub_category->update($validated);

        return redirect()->route('party-sub-categories.index')
            ->with('success', 'Party sub category updated successfully.');
    }

    public function destroy(PartySubCategory $party_sub_category)
    {
        $party_sub_category->delete();

        return redirect()->route('party-sub-categories.index')
            ->with('success', 'Party sub category deleted successfully.');
    }
}
