<?php

namespace App\Http\Controllers;

use App\Models\LandCutting;
use App\Models\Sale;
use App\Support\LandMeasure;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class SaleLandCuttingController extends Controller
{
    public function index(Sale $sale)
    {
        $sale->load(['project', 'landCuttings']);

        return view('sales.land-cuttings.index', compact('sale'));
    }

    public function store(Request $request, Sale $sale)
    {
        $validated = $request->validate([
            'cutting_type' => ['required', 'string', Rule::in(array_keys(LandCutting::TYPES))],
            'area_acre' => ['required', 'integer', 'min:0'],
            'area_kanal' => ['required', 'integer', 'min:0'],
            'area_marla' => ['required', 'integer', 'min:0'],
            'area_sqft' => ['required', 'integer', 'min:0'],
        ]);

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

        LandCutting::create([
            'sale_id' => $sale->id,
            'project_id' => $sale->project_id,
            'cutting_type' => $validated['cutting_type'],
            'area_acre' => $validated['area_acre'],
            'area_kanal' => $validated['area_kanal'],
            'area_marla' => $validated['area_marla'],
            'area_sqft' => $validated['area_sqft'],
            'land_area_marla' => round($marla, 4),
        ]);

        return redirect()->route('sale.records.land-cuttings.index', $sale)
            ->with('success', 'Land cutting added.');
    }

    public function destroy(Sale $sale, LandCutting $land_cutting)
    {
        abort_unless((int) $land_cutting->sale_id === (int) $sale->id, 404);
        $land_cutting->delete();

        return redirect()->route('sale.records.land-cuttings.index', $sale)
            ->with('success', 'Land cutting removed.');
    }
}
