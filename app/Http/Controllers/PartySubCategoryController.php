<?php

namespace App\Http\Controllers;

use App\Models\PartyCategory;
use App\Models\PartySubCategory;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PartySubCategoryController extends Controller
{
    /**
     * @return array<string, mixed>
     */
    private function subCategoryRules(): array
    {
        return [
            'category_id' => ['required', 'integer', 'exists:party_categories,id'],
            'name' => ['required', 'string', 'max:255'],
            'unit' => ['nullable', 'string', 'max:50', Rule::in(config('construction_units', []))],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedSubCategory(Request $request): array
    {
        $request->merge([
            'unit' => $request->input('unit') ?: null,
        ]);

        return $request->validate($this->subCategoryRules());
    }

    public function index(Request $request)
    {
        $name = trim((string) $request->query('name', ''));
        $categoryId = $request->query('category_id');
        $categoryId = $categoryId !== null && $categoryId !== ''
            ? (int) $categoryId
            : null;

        $partySubCategories = PartySubCategory::query()
            ->with('category')
            ->when($name !== '', function ($query) use ($name) {
                $query->where('name', 'like', '%'.$name.'%');
            })
            ->when($categoryId, function ($query) use ($categoryId) {
                $query->where('category_id', $categoryId);
            })
            ->orderBy('id', 'desc')
            ->paginate(10)
            ->withQueryString();

        $partyCategories = PartyCategory::orderBy('name')->get();
        $hasFilters = $name !== '' || $categoryId;

        return view('party-sub-categories.index', compact(
            'partySubCategories',
            'partyCategories',
            'name',
            'categoryId',
            'hasFilters'
        ));
    }

    public function create()
    {
        $partyCategories = PartyCategory::orderBy('name')->get();

        return view('party-sub-categories.create', compact('partyCategories'));
    }

    public function store(Request $request)
    {
        $validated = $this->validatedSubCategory($request);

        PartySubCategory::create([
            'category_id' => $validated['category_id'],
            'name' => $validated['name'],
            'unit' => $validated['unit'] ?? null,
        ]);

        return redirect()->route('party-sub-categories.index')
            ->with('success', 'Party sub category created successfully.');
    }

    /**
     * Create a party sub category from the daybook modal (JSON).
     */
    public function quickStore(Request $request)
    {
        $validated = $this->validatedSubCategory($request);

        $partySubCategory = PartySubCategory::create([
            'category_id' => $validated['category_id'],
            'name' => trim($validated['name']),
            'unit' => $validated['unit'] ?? null,
        ]);
        $partySubCategory->load('category');

        return response()->json([
            'id' => $partySubCategory->id,
            'label' => ($partySubCategory->category?->name ?? '—').' — '.$partySubCategory->name,
        ]);
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
        $validated = $this->validatedSubCategory($request);

        $party_sub_category->update([
            'category_id' => $validated['category_id'],
            'name' => $validated['name'],
            'unit' => $validated['unit'] ?? null,
        ]);

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
