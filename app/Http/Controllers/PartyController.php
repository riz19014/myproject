<?php

namespace App\Http\Controllers;

use App\Models\DayBookEntry;
use App\Models\Party;
use App\Models\PartySubCategory;
use App\Support\CnicFormat;
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
        $partySubCategories = $this->partySubCategories();

        return view('parties.create', compact('partySubCategories'));
    }

    public function store(Request $request)
    {
        $this->normalizePartyInput($request);

        $validated = $request->validate(
            $this->partyFieldRules(),
            $this->partyFieldMessages()
        );

        Party::create($this->partyAttributesFromValidated($validated));

        return redirect()->route('parties.index')
            ->with('success', 'Party created successfully.');
    }

    public function edit(Party $party)
    {
        $partySubCategories = $this->partySubCategories();

        return view('parties.edit', compact('party', 'partySubCategories'));
    }

    public function update(Request $request, Party $party)
    {
        $this->normalizePartyInput($request);

        $validated = $request->validate(
            $this->partyFieldRules(),
            $this->partyFieldMessages()
        );

        $party->update($this->partyAttributesFromValidated($validated));

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
     * Create a party from daybook or purchase file modals (JSON).
     */
    public function quickStore(Request $request)
    {
        $this->normalizePartyInput($request);

        $validated = $request->validate(
            $this->partyFieldRules(includeOpeningBalance: false),
            $this->partyFieldMessages()
        );

        $party = Party::create($this->partyAttributesFromValidated($validated, openingBalance: 0));

        return response()->json([
            'id' => $party->id,
            'name' => $party->name,
        ]);
    }

    private function normalizePartyInput(Request $request): void
    {
        if ($request->has('phone')) {
            $phone = $request->input('phone');
            $request->merge([
                'phone' => $phone !== null && $phone !== ''
                    ? preg_replace('/\D/', '', (string) $phone)
                    : null,
            ]);
        }

        if ($request->has('cnic')) {
            $cnic = $request->input('cnic');
            $request->merge([
                'cnic' => $cnic !== null && $cnic !== ''
                    ? CnicFormat::digits((string) $cnic)
                    : null,
            ]);
        }
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, PartySubCategory>
     */
    private function partySubCategories()
    {
        return PartySubCategory::query()
            ->with('category')
            ->orderBy('category_id')
            ->orderBy('name')
            ->get();
    }

    /**
     * @return array<string, mixed>
     */
    private function partyFieldRules(bool $includeOpeningBalance = true): array
    {
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'sub_category_id' => ['required', 'integer', 'exists:party_sub_categories,id'],
            'phone' => ['nullable', 'string', 'regex:/^\d{11}$/'],
            'cnic' => ['nullable', 'string', 'regex:/^\d{13}$/'],
            'address' => ['nullable', 'string', 'max:2000'],
        ];

        if ($includeOpeningBalance) {
            $rules['opening_balance'] = ['nullable', 'numeric'];
        }

        return $rules;
    }

    /**
     * @return array<string, string>
     */
    private function partyFieldMessages(): array
    {
        return [
            'phone.regex' => 'Phone must be exactly 11 digits (numbers only).',
            'cnic.regex' => 'CNIC must be 13 digits in format 23012-2321373-1.',
        ];
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function partyAttributesFromValidated(array $validated, ?float $openingBalance = null): array
    {
        $sub = PartySubCategory::query()->findOrFail($validated['sub_category_id']);

        $attrs = [
            'name' => trim($validated['name']),
            'phone' => isset($validated['phone']) && $validated['phone'] !== '' ? $validated['phone'] : null,
            'cnic' => isset($validated['cnic']) && $validated['cnic'] !== ''
                ? CnicFormat::digits($validated['cnic'])
                : null,
            'address' => isset($validated['address']) ? trim((string) $validated['address']) : null,
            'category_id' => $sub->category_id,
            'sub_category_id' => $sub->id,
        ];

        if ($openingBalance !== null) {
            $attrs['opening_balance'] = $openingBalance;
        } elseif (array_key_exists('opening_balance', $validated)) {
            $attrs['opening_balance'] = $validated['opening_balance'] ?? 0;
        }

        if ($attrs['address'] === '') {
            $attrs['address'] = null;
        }

        return $attrs;
    }
}
