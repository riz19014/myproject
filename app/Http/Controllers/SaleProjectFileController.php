<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Party;
use App\Models\Project;
use App\Models\ProjectFile;
use App\Models\Sale;
use App\Support\LandMeasure;
use App\Support\ProjectExemptionDefaults;
use App\Support\SaleExemptionConfig;
use App\Support\SaleExemptionFileCalculator;
use App\Support\SaleExemptionRules;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class SaleProjectFileController extends Controller
{
    public function index(Project $project)
    {
        $project->load(['landType', 'projectFiles' => fn ($q) => $q->with(['dealerParty', 'sales'])->orderBy('file_number')]);

        return view('sales.files.index', compact('project'));
    }

    public function percentageIndex()
    {
        $files = ProjectFile::query()
            ->with(['project', 'dealerParty'])
            ->join('projects', 'projects.id', '=', 'project_files.project_id')
            ->orderBy('projects.name')
            ->orderBy('project_files.file_number')
            ->select('project_files.*')
            ->get();

        $projects = Project::query()->orderBy('name')->get();

        return view('sales.percentage-index', compact('files', 'projects'));
    }

    public function create(Project $project)
    {
        $parties = Party::query()->orderBy('name')->get();

        return view('sales.files.form', compact('project', 'parties'));
    }

    public function store(Request $request, Project $project)
    {
        $validated = $request->validate([
            'file_number' => ['required', 'string', 'max:100'],
            'dealer_party_id' => ['nullable', 'integer', 'exists:parties,id'],
            'area_acre' => ['required', 'integer', 'min:0'],
            'area_kanal' => ['required', 'integer', 'min:0'],
            'area_marla' => ['required', 'integer', 'min:0'],
            'area_sqft' => ['required', 'integer', 'min:0'],
            'notes' => ['nullable', 'string'],
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

        ProjectExemptionDefaults::ensureForProject($project);

        $project->projectFiles()->create([
            'file_number' => $validated['file_number'],
            'dealer_party_id' => $validated['dealer_party_id'] ?? null,
            'area_acre' => $validated['area_acre'],
            'area_kanal' => $validated['area_kanal'],
            'area_marla' => $validated['area_marla'],
            'area_sqft' => $validated['area_sqft'],
            'land_area_marla' => round($marla, 4),
            'notes' => $validated['notes'] ?? null,
            'status' => 'available',
        ]);

        return redirect()->route('sale.files.index', $project)
            ->with('success', 'File added successfully.');
    }

    public function saleCreate(ProjectFile $projectFile)
    {
        $projectFile->load(['project.landType', 'sales.customer', 'dealerParty', 'exemptionOverrides']);
        $config = SaleExemptionConfig::forFile($projectFile);
        $customers = Customer::query()->orderBy('name')->get();
        $fileMarla = (float) $projectFile->land_area_marla;
        $poolsSummary = $config->poolsSummary($fileMarla);
        $poolsByComponent = $poolsSummary['pools'];
        $usedByComponent = [];
        foreach ($config->components() as $component) {
            $usedByComponent[$component->slug] = (float) $projectFile->sales
                ->where('sale_type', Sale::TYPE_PERCENTAGE)
                ->where('component', $component->slug)
                ->sum('land_area_marla');
        }
        $remainingDirect = $projectFile->remainingMarla();
        $plotRates = $projectFile->plot_sale_rates_per_acre ?? [];
        $fileCalculator = SaleExemptionFileCalculator::calculate($fileMarla, $config, $plotRates);
        $saleAmountPerAcre = old('sale_amount_per_acre', $projectFile->sale_amount_per_acre);
        $landValueEstimate = ($saleAmountPerAcre !== null && $saleAmountPerAcre !== '' && (float) $saleAmountPerAcre > 0)
            ? round((float) $fileCalculator['acres'] * (float) $saleAmountPerAcre, 2)
            : null;

        return view('sales.create', [
            'projectFile' => $projectFile,
            'project' => $projectFile->project,
            'customers' => $customers,
            'exemptionConfig' => $config,
            'exemptionJson' => $config->toFrontendJson(),
            'poolsByComponent' => $poolsByComponent,
            'usedByComponent' => $usedByComponent,
            'poolsSummary' => $poolsSummary,
            'remainingDirect' => $remainingDirect,
            'fileCalculator' => $fileCalculator,
            'plotRatesPerFile' => $plotRates,
            'saleAmountPerAcre' => $saleAmountPerAcre,
            'landValueEstimate' => $landValueEstimate,
            'recentSales' => $projectFile->sales->sortByDesc('id')->take(20),
        ]);
    }

    public function updateArea(Request $request, ProjectFile $projectFile)
    {
        $projectFile->load('project');
        $config = SaleExemptionConfig::forProject($projectFile->project);

        $rules = [
            'file_area_acre' => ['required', 'integer', 'min:0'],
            'file_area_kanal' => ['required', 'integer', 'min:0'],
            'file_area_marla' => ['required', 'integer', 'min:0'],
            'file_area_sqft' => ['required', 'integer', 'min:0'],
            'pool_percent' => ['required', 'array'],
            'plot_rate_per_file' => ['nullable', 'array'],
            'plot_rate_per_file.*' => ['nullable', 'numeric', 'min:0'],
            'sale_amount_per_acre' => ['nullable', 'numeric', 'min:0'],
        ];
        foreach ($config->components() as $component) {
            $rules['pool_percent.'.$component->id] = ['required', 'numeric', 'min:0', 'max:100', 'regex:/^\d+(\.\d{1,4})?$/'];
        }

        $validated = $request->validate($rules, [
            'pool_percent.*.regex' => 'Pool % may have up to 4 decimal places.',
        ]);

        $marla = LandMeasure::marlaFromAkms(
            (int) $validated['file_area_acre'],
            (int) $validated['file_area_kanal'],
            (int) $validated['file_area_marla'],
            (int) $validated['file_area_sqft']
        );
        if ($marla <= 0) {
            throw ValidationException::withMessages([
                'file_area_acre' => ['Enter at least one positive whole number in Acre, Kanal, Marla, or Sq ft.'],
            ]);
        }

        $projectFile->update([
            'area_acre' => $validated['file_area_acre'],
            'area_kanal' => $validated['file_area_kanal'],
            'area_marla' => $validated['file_area_marla'],
            'area_sqft' => $validated['file_area_sqft'],
            'land_area_marla' => round($marla, 4),
        ]);

        foreach ($config->components() as $component) {
            $pct = round((float) ($validated['pool_percent'][$component->id] ?? $component->pool_percent), 4);
            $projectFile->exemptionOverrides()->updateOrCreate(
                ['component_id' => $component->id],
                ['pool_percent' => $pct]
            );
            if ($component->slug === SaleExemptionRules::COMPONENT_RESIDENTIAL) {
                $projectFile->update(['residential_pool_percent' => $pct]);
            }
            if ($component->slug === SaleExemptionRules::COMPONENT_COMMERCIAL) {
                $projectFile->update(['commercial_pool_percent' => $pct]);
            }
        }

        $plotRates = [];
        foreach ($validated['plot_rate_per_file'] ?? [] as $slug => $rate) {
            if ($rate === null || $rate === '') {
                continue;
            }
            $plotRates[(string) $slug] = round((float) $rate, 2);
        }
        $projectFile->update(['plot_sale_rates_per_acre' => $plotRates !== [] ? $plotRates : null]);

        $saleAmountPerAcre = $validated['sale_amount_per_acre'] ?? null;
        $projectFile->update([
            'sale_amount_per_acre' => ($saleAmountPerAcre !== null && $saleAmountPerAcre !== '')
                ? round((float) $saleAmountPerAcre, 2)
                : null,
        ]);

        return redirect()->route('sale.files.sale.create', [
            'projectFile' => $projectFile,
            'type' => Sale::TYPE_PERCENTAGE,
        ])->with('success', 'File settings saved.');
    }

    public function estimationPdf(ProjectFile $projectFile)
    {
        $projectFile->load(['project.landType', 'exemptionOverrides']);
        $project = $projectFile->project;
        $config = SaleExemptionConfig::forFile($projectFile);
        $fileMarla = (float) $projectFile->land_area_marla;
        $plotRates = $projectFile->plot_sale_rates_per_acre ?? [];
        $fileCalculator = SaleExemptionFileCalculator::calculate($fileMarla, $config, $plotRates);
        $poolsSummary = $config->poolsSummary($fileMarla);
        $saleAmountPerAcre = $projectFile->sale_amount_per_acre;
        $landValueEstimate = ($saleAmountPerAcre !== null && (float) $saleAmountPerAcre > 0)
            ? round((float) $fileCalculator['acres'] * (float) $saleAmountPerAcre, 2)
            : null;

        $pdf = Pdf::loadView('sales.estimation-pdf', [
            'projectFile' => $projectFile,
            'project' => $project,
            'config' => $config,
            'fileMarla' => $fileMarla,
            'marlaPerAcreLand' => $config->marlaPerAcreLand(),
            'fileCalculator' => $fileCalculator,
            'poolsByComponent' => $poolsSummary['pools'],
            'saleAmountPerAcre' => $saleAmountPerAcre,
            'landValueEstimate' => $landValueEstimate,
            'generatedAt' => now(),
        ]);
        $pdf->setPaper('a4', 'landscape');

        $safeProject = preg_replace('/[^a-zA-Z0-9_-]+/', '-', $project->name) ?: 'project';
        $safeFile = preg_replace('/[^a-zA-Z0-9_-]+/', '-', $projectFile->file_number) ?: 'file';

        return $pdf->download('sale-estimation-'.$safeProject.'-'.$safeFile.'-'.now()->format('Y-m-d').'.pdf');
    }
}
