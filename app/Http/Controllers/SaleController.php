<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Project;
use App\Models\ProjectFile;
use App\Models\Sale;
use App\Models\SaleParticipant;
use App\Support\LandMeasure;
use App\Support\SaleExemptionConfig;
use App\Support\SaleExemptionRules;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class SaleController extends Controller
{
    public function index()
    {
        $projects = Project::query()
            ->with('landType')
            ->withCount('projectFiles')
            ->orderBy('name')
            ->get();

        return view('sales.index', compact('projects'));
    }

    public function store(Request $request, ProjectFile $projectFile)
    {
        $projectFile->load('project');
        $saleType = $request->input('sale_type', Sale::TYPE_DIRECT);

        if ($saleType === Sale::TYPE_PERCENTAGE) {
            return $this->storePercentage($request, $projectFile);
        }

        return $this->storeDirect($request, $projectFile);
    }

    private function storeDirect(Request $request, ProjectFile $projectFile): \Illuminate\Http\RedirectResponse
    {
        $validated = $request->validate([
            'customer_id' => ['required', 'integer', 'exists:customers,id'],
            'area_acre' => ['required', 'integer', 'min:0'],
            'area_kanal' => ['required', 'integer', 'min:0'],
            'area_marla' => ['required', 'integer', 'min:0'],
            'area_sqft' => ['required', 'integer', 'min:0'],
            'total_amount' => ['required', 'numeric', 'min:0'],
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

        $remaining = $projectFile->remainingMarla();
        if ($marla > $remaining + 0.0001) {
            throw ValidationException::withMessages([
                'area_acre' => ['Plot size exceeds remaining file land ('.LandMeasure::formatAkmsLabelFromMarla($remaining).').'],
            ]);
        }

        Sale::create([
            'project_id' => $projectFile->project_id,
            'project_file_id' => $projectFile->id,
            'sale_type' => Sale::TYPE_DIRECT,
            'customer_id' => $validated['customer_id'],
            'area_acre' => $validated['area_acre'],
            'area_kanal' => $validated['area_kanal'],
            'area_marla' => $validated['area_marla'],
            'area_sqft' => $validated['area_sqft'],
            'land_area_marla' => round($marla, 4),
            'total_amount' => $validated['total_amount'],
        ]);

        return redirect()->route('sale.files.sale.create', [
            'projectFile' => $projectFile,
            'type' => Sale::TYPE_DIRECT,
        ])->with('success', 'Direct sale recorded.');
    }

    private function storePercentage(Request $request, ProjectFile $projectFile): \Illuminate\Http\RedirectResponse
    {
        $projectFile->load('project');
        $config = SaleExemptionConfig::forFile($projectFile);
        $componentSlugs = $config->componentSlugs();

        $validated = $request->validate([
            'customer_id' => ['required', 'integer', 'exists:customers,id'],
            'component' => ['required', 'string', 'max:40', Rule::in($componentSlugs)],
            'plot_type' => ['required', 'string', 'max:40'],
            'plot_quantity' => ['required', 'integer', 'min:1', 'max:999'],
            'total_amount' => ['required', 'numeric', 'min:0'],
        ]);

        $component = $validated['component'];
        $plotType = $validated['plot_type'];
        $options = $config->plotOptionsForComponent($component);
        if (! array_key_exists($plotType, $options)) {
            throw ValidationException::withMessages([
                'plot_type' => ['Invalid plot type for this component.'],
            ]);
        }

        $marlaPerPlot = $config->plotMarla($component, $plotType);
        $qty = (int) $validated['plot_quantity'];
        $totalMarla = round($marlaPerPlot * $qty, 4);

        $fileMarla = (float) $projectFile->land_area_marla;
        $pool = $config->poolMarla($fileMarla, $component);
        $componentLabel = $config->findComponent($component)?->label ?? $component;

        $used = (float) Sale::query()
            ->where('project_file_id', $projectFile->id)
            ->where('sale_type', Sale::TYPE_PERCENTAGE)
            ->where('component', $component)
            ->sum('land_area_marla');

        if ($used + $totalMarla > $pool + 0.0001) {
            throw ValidationException::withMessages([
                'plot_quantity' => [
                    'Exceeds '.$componentLabel
                    .' pool. Available: '.LandMeasure::formatAkmsLabelFromMarla(max(0, $pool - $used)).'.',
                ],
            ]);
        }

        Sale::create([
            'project_id' => $projectFile->project_id,
            'project_file_id' => $projectFile->id,
            'sale_type' => Sale::TYPE_PERCENTAGE,
            'component' => $component,
            'plot_type' => $plotType,
            'plot_quantity' => $qty,
            'customer_id' => $validated['customer_id'],
            'area_acre' => 0,
            'area_kanal' => 0,
            'area_marla' => 0,
            'area_sqft' => 0,
            'land_area_marla' => $totalMarla,
            'total_amount' => $validated['total_amount'],
        ]);

        return redirect()->route('sale.files.sale.create', [
            'projectFile' => $projectFile,
            'type' => Sale::TYPE_PERCENTAGE,
        ])->with('success', 'Percentage sale recorded.');
    }

    /** @deprecated Legacy project-level sale; kept for old URLs. */
    public function create(Request $request)
    {
        $projectId = $request->query('project');
        if ($projectId) {
            $project = Project::query()->findOrFail((int) $projectId);

            return redirect()->route('sale.files.index', $project);
        }

        return redirect()->route('sale.index');
    }
}
