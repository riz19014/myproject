<?php

namespace App\Http\Controllers;

use App\Models\Party;
use App\Models\PartySubCategory;
use Illuminate\Http\Request;

class PartyController extends Controller
{
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
