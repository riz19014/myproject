<?php

namespace App\Http\Controllers;

use App\Models\DayBookEntry;
use App\Models\Party;
use App\Models\PartySubCategory;
use Illuminate\Http\Request;

class PartyController extends Controller
{
    public function index()
    {
        $parties = Party::query()
            ->with(['category', 'subCategory'])
            ->orderBy('id', 'desc')
            ->paginate(10);

        return view('parties.index', compact('parties'));
    }

    public function create()
    {
        $partySubCategories = PartySubCategory::query()
            ->with('category')
            ->orderBy('category_id')
            ->orderBy('name')
            ->get();

        return view('parties.create', compact('partySubCategories'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'sub_category_id' => ['required', 'integer', 'exists:party_sub_categories,id'],
            'phone' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:2000'],
            'opening_balance' => ['nullable', 'numeric'],
        ]);

        $sub = PartySubCategory::query()->findOrFail($validated['sub_category_id']);

        Party::create([
            'name' => $validated['name'],
            'phone' => $validated['phone'] ?? null,
            'address' => $validated['address'] ?? null,
            'category_id' => $sub->category_id,
            'sub_category_id' => $sub->id,
            'opening_balance' => $validated['opening_balance'] ?? 0,
        ]);

        return redirect()->route('parties.index')
            ->with('success', 'Party created successfully.');
    }

    public function edit(Party $party)
    {
        $partySubCategories = PartySubCategory::query()
            ->with('category')
            ->orderBy('category_id')
            ->orderBy('name')
            ->get();

        return view('parties.edit', compact('party', 'partySubCategories'));
    }

    public function update(Request $request, Party $party)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'sub_category_id' => ['required', 'integer', 'exists:party_sub_categories,id'],
            'phone' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:2000'],
            'opening_balance' => ['nullable', 'numeric'],
        ]);

        $sub = PartySubCategory::query()->findOrFail($validated['sub_category_id']);

        $party->update([
            'name' => $validated['name'],
            'phone' => $validated['phone'] ?? null,
            'address' => $validated['address'] ?? null,
            'category_id' => $sub->category_id,
            'sub_category_id' => $sub->id,
            'opening_balance' => $validated['opening_balance'] ?? 0,
        ]);

        return redirect()->route('parties.index')
            ->with('success', 'Party updated successfully.');
    }

    public function destroy(Party $party)
    {
        $linked = DayBookEntry::query()
            ->where('link_type', DayBookEntry::LINK_PARTY)
            ->where('link_id', $party->id)
            ->exists();

        if ($linked) {
            return redirect()->route('parties.index')
                ->with('error', 'Cannot delete this party because daybook entries are linked to it.');
        }

        $party->delete();

        return redirect()->route('parties.index')
            ->with('success', 'Party deleted successfully.');
    }

    /**
     * Create a party from the daybook page (JSON). Category is taken from the sub category.
     */
    public function quickStore(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'sub_category_id' => ['required', 'integer', 'exists:party_sub_categories,id'],
        ]);

        $sub = PartySubCategory::query()->findOrFail($validated['sub_category_id']);

        $party = Party::create([
            'name' => $validated['name'],
            'phone' => null,
            'address' => null,
            'category_id' => $sub->category_id,
            'sub_category_id' => $sub->id,
            'opening_balance' => 0,
        ]);

        return response()->json([
            'id' => $party->id,
            'name' => $party->name,
        ]);
    }
}
