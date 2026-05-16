<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Party;
use App\Models\Project;
use App\Models\Sale;
use App\Models\SaleParticipant;
use App\Support\LandMeasure;
use Illuminate\Http\Request;

class SaleController extends Controller
{
    public function create(Request $request)
    {
        $projectId = $request->query('project');
        if ($projectId === null || $projectId === '') {
            $projects = Project::query()
                ->with('landType')
                ->orderBy('name')
                ->get();

            return view('sales.create-select-project', compact('projects'));
        }

        $project = Project::query()
            ->findOrFail((int) $projectId);

        $parties = Party::query()->with(['subCategory.category'])->orderBy('name')->get();
        $customers = Customer::query()->orderBy('name')->get();

        return view('sales.create-details', compact('project', 'parties', 'customers'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'project_id' => ['required', 'integer', 'exists:projects,id'],
            'area_acre' => ['required', 'integer', 'min:0'],
            'area_kanal' => ['required', 'integer', 'min:0'],
            'area_marla' => ['required', 'integer', 'min:0'],
            'area_sqft' => ['required', 'integer', 'min:0'],
            'total_amount' => ['required', 'numeric', 'min:0'],
            'party_ids' => ['nullable', 'array'],
            'party_ids.*' => ['integer', 'distinct', 'exists:parties,id'],
            'customer_ids' => ['nullable', 'array'],
            'customer_ids.*' => ['integer', 'distinct', 'exists:customers,id'],
        ]);

        $project = Project::query()->findOrFail($validated['project_id']);

        $marla = LandMeasure::marlaFromAkms(
            (int) $validated['area_acre'],
            (int) $validated['area_kanal'],
            (int) $validated['area_marla'],
            (int) $validated['area_sqft']
        );
        if ($marla <= 0) {
            throw ValidationException::withMessages([
                'area_acre' => ['Enter at least one positive whole number in Acre, Kanal, Marla, or Sq ft.'],
            ]);
        }

        $partyIds = collect($validated['party_ids'] ?? [])->map(fn ($id) => (int) $id)->unique()->values()->all();
        $customerIds = collect($validated['customer_ids'] ?? [])->map(fn ($id) => (int) $id)->unique()->values()->all();

        if (count($partyIds) === 0 && count($customerIds) === 0) {
            throw ValidationException::withMessages([
                'party_ids' => ['Select at least one party (dealer) or buyer (customer).'],
            ]);
        }

        $sale = Sale::create([
            'project_id' => $project->id,
            'area_acre' => $validated['area_acre'],
            'area_kanal' => $validated['area_kanal'],
            'area_marla' => $validated['area_marla'],
            'area_sqft' => $validated['area_sqft'],
            'land_area_marla' => round($marla, 4),
            'total_amount' => $validated['total_amount'],
        ]);

        foreach ($partyIds as $pid) {
            SaleParticipant::create([
                'sale_id' => $sale->id,
                'party_id' => $pid,
                'customer_id' => null,
            ]);
        }
        foreach ($customerIds as $cid) {
            SaleParticipant::create([
                'sale_id' => $sale->id,
                'party_id' => null,
                'customer_id' => $cid,
            ]);
        }

        return redirect()->route('sale.index')->with('success', 'Sale recorded successfully.');
    }
}
