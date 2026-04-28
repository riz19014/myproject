<?php

namespace App\Http\Controllers;

use App\Models\LandType;
use App\Models\PartySubCategory;
use Illuminate\Http\Request;

class LandTypeController extends Controller
{
    public function index()
    {
        $landTypes = LandType::query()
            ->with('partySubCategory.category')
            ->orderBy('name')
            ->paginate(15);

        return view('land-types.index', compact('landTypes'));
    }

    public function create()
    {
        $partySubCategories = PartySubCategory::query()
            ->with('category')
            ->orderBy('category_id')
            ->orderBy('name')
            ->get();

        return view('land-types.create', compact('partySubCategories'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'party_sub_category_id' => ['nullable', 'integer', 'exists:party_sub_categories,id'],
        ]);

        LandType::create($validated);

        return redirect()->route('land-types.index')
            ->with('success', 'Land type created successfully.');
    }

    public function edit(LandType $land_type)
    {
        $partySubCategories = PartySubCategory::query()
            ->with('category')
            ->orderBy('category_id')
            ->orderBy('name')
            ->get();

        return view('land-types.edit', [
            'landType' => $land_type,
            'partySubCategories' => $partySubCategories,
        ]);
    }

    public function update(Request $request, LandType $land_type)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'party_sub_category_id' => ['nullable', 'integer', 'exists:party_sub_categories,id'],
        ]);

        $land_type->update($validated);

        return redirect()->route('land-types.index')
            ->with('success', 'Land type updated successfully.');
    }

    public function destroy(LandType $land_type)
    {
        if ($land_type->projects()->exists()) {
            return redirect()->route('land-types.index')
                ->with('error', 'Cannot delete this land type: one or more projects use it.');
        }

        $land_type->delete();

        return redirect()->route('land-types.index')
            ->with('success', 'Land type deleted successfully.');
    }
}
