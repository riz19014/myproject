<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\FileSaleCollective;
use App\Models\Party;
use App\Models\Project;
use App\Models\ProjectFile;
use App\Models\Sale;
use App\Support\FileSaleLandService;
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
    public function index(Project $project, FileSaleLandService $fileSaleLandService)
    {
        $project->load(['landType', 'projectFiles' => fn ($q) => $q->with(['dealerParty', 'sales'])->orderBy('file_number')]);

        $fileSaleSummary = $fileSaleLandService->buildFileSaleSummary($project);
        $exemptionOptions = $project->saleExemptionSnapshots()->get()->map(fn ($snapshot) => [
            'id' => (int) $snapshot->id,
            'label' => $snapshot->summaryLabel()
                .' · 1 acre = '.rtrim(rtrim(number_format($snapshot->marlaPerAcre(), 4, '.', ''), '0'), '.').'M'
                .' · '.$snapshot->created_at->format('d M Y'),
        ])->values()->all();

        return view('sales.files.index', compact('project', 'fileSaleSummary', 'exemptionOptions'));
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

    public function originalFormulaPdf(Project $project, FileSaleLandService $fileSaleLandService)
    {
        $project->load('landType');
        $summary = $fileSaleLandService->buildFileSaleSummary($project);

        $pdf = Pdf::loadView('sales.files.original-formula-pdf', [
            'project' => $project,
            'totalLand' => $summary['totals']['total_land_area'] ?? '—',
            'files' => collect($summary['moved_files'] ?? [])->pluck('name')->filter()->values()->all(),
            'areaBalance' => $summary['area_balance'] ?? [
                'formula_columns' => [],
                'moza_groups' => [],
                'totals' => ['total_land' => '—', 'formula_values' => []],
            ],
            'generatedAt' => now(),
        ]);
        $pdf->setPaper('a4', 'landscape');

        $safeProject = preg_replace('/[^a-zA-Z0-9_-]+/', '-', $project->name) ?: 'project';

        return $pdf->download('total-land-'.$safeProject.'-'.now()->format('Y-m-d').'.pdf');
    }

    public function leftoverLandBalancePdf(Project $project, FileSaleLandService $fileSaleLandService)
    {
        $project->load('landType');
        $summary = $fileSaleLandService->buildFileSaleSummary($project);
        $leftover = $summary['leftover_balance'] ?? ['formula_columns' => [], 'files' => [], 'totals' => []];

        $pdf = Pdf::loadView('sales.files.leftover-land-balance-pdf', [
            'project' => $project,
            'leftoverColumns' => $leftover['formula_columns'] ?? [],
            'leftoverFiles' => $leftover['files'] ?? [],
            'leftoverTotals' => $leftover['totals'] ?? [],
            'generatedAt' => now(),
        ]);
        $pdf->setPaper('a4', 'landscape');

        $safeProject = preg_replace('/[^a-zA-Z0-9_-]+/', '-', $project->name) ?: 'project';

        return $pdf->download('leftover-land-'.$safeProject.'-'.now()->format('Y-m-d').'.pdf');
    }


    public function showCollective(Project $project, FileSaleCollective $collective, FileSaleLandService $fileSaleLandService)
    {
        abort_unless((int) $collective->project_id === (int) $project->id, 404);

        $project->load('landType');
        $summary = $fileSaleLandService->buildFileSaleSummary($project);
        $collectiveSummary = collect($summary['collectives'] ?? [])
            ->firstWhere('id', (int) $collective->id);

        abort_unless(is_array($collectiveSummary), 404);

        $currentPayload = $fileSaleLandService->currentExemptionPayload($project);
        $currentComponents = collect($currentPayload['components'] ?? [])->map(function (array $component) {
            $pct = rtrim(rtrim(number_format((float) ($component['pool_percent'] ?? 0), 4, '.', ''), '0'), '.');

            return [
                'label' => trim((string) ($component['label'] ?? $component['slug'] ?? '')),
                'percent' => $pct.'%',
                'plots' => collect($component['plot_types'] ?? [])->map(fn (array $plot) => [
                    'label' => (string) ($plot['label'] ?? $plot['slug'] ?? ''),
                ])->values()->all(),
            ];
        })->values()->all();

        $currentOption = [
            'id' => null,
            'key' => 'current',
            'title' => 'Current project setup',
            'badge' => 'Live',
            'summary' => collect($currentComponents)->map(fn (array $c) => trim(($c['label'] ?? '').' '.($c['percent'] ?? '')))->filter()->implode(' · ') ?: '—',
            'marla_per_acre' => (float) ($currentPayload['marla_per_acre'] ?? 160),
            'marla_label' => '1 acre = '.rtrim(rtrim(number_format((float) ($currentPayload['marla_per_acre'] ?? 160), 4, '.', ''), '0'), '.').'M',
            'date_label' => 'Always uses the latest project exemption',
            'components' => $currentComponents,
            'is_current' => true,
        ];

        $snapshotOptions = $project->saleExemptionSnapshots()->get()->map(function ($snapshot) {
            $components = collect($snapshot->components())->map(function (array $component) {
                $pct = rtrim(rtrim(number_format((float) ($component['pool_percent'] ?? 0), 4, '.', ''), '0'), '.');

                return [
                    'label' => trim((string) ($component['label'] ?? $component['slug'] ?? '')),
                    'percent' => $pct.'%',
                    'plots' => collect($component['plot_types'] ?? [])->map(fn (array $plot) => [
                        'label' => (string) ($plot['label'] ?? $plot['slug'] ?? ''),
                    ])->values()->all(),
                ];
            })->values()->all();

            return [
                'id' => (int) $snapshot->id,
                'key' => 'snapshot-'.$snapshot->id,
                'title' => 'Saved exemption',
                'badge' => $snapshot->created_at->format('d M Y'),
                'summary' => $snapshot->summaryLabel(),
                'marla_per_acre' => $snapshot->marlaPerAcre(),
                'marla_label' => '1 acre = '.rtrim(rtrim(number_format($snapshot->marlaPerAcre(), 4, '.', ''), '0'), '.').'M',
                'date_label' => 'Saved '.$snapshot->created_at->format('d M Y, h:i A'),
                'components' => $components,
                'is_current' => false,
            ];
        })->values()->all();

        $exemptionOptions = collect([$currentOption])->merge($snapshotOptions)->values()->all();

        $activePayload = is_array($collectiveSummary['exemption_payload'] ?? null)
            ? $collectiveSummary['exemption_payload']
            : ($collective->exemption_payload ?: $currentPayload);

        $formatPct = static function (float $value): string {
            return rtrim(rtrim(number_format($value, 4, '.', ''), '0'), '.') ?: '0';
        };
        $formatNum = static function (float $value): string {
            return rtrim(rtrim(number_format($value, 4, '.', ''), '0'), '.') ?: '0';
        };

        $activeComponents = collect($activePayload['components'] ?? [])->map(function (array $component) use ($formatPct, $formatNum) {
            $pool = (float) ($component['pool_percent'] ?? 0);

            return [
                'slug' => (string) ($component['slug'] ?? ''),
                'label' => trim((string) ($component['label'] ?? $component['slug'] ?? '')),
                'pool_percent' => $pool,
                'pool_percent_label' => $formatPct($pool).'%',
                'plot_types' => collect($component['plot_types'] ?? [])->map(function (array $plot) use ($formatPct, $formatNum) {
                    return [
                        'slug' => (string) ($plot['slug'] ?? ''),
                        'label' => (string) ($plot['label'] ?? $plot['slug'] ?? ''),
                        'marla_per_plot' => (float) ($plot['marla_per_plot'] ?? 0),
                        'marla_per_plot_label' => $formatNum((float) ($plot['marla_per_plot'] ?? 0)).'M',
                        'nominal_marla' => (float) ($plot['nominal_marla'] ?? 0),
                        'nominal_marla_label' => $formatNum((float) ($plot['nominal_marla'] ?? 0)).'M',
                        'share_percent' => (float) ($plot['share_percent'] ?? 0),
                        'share_percent_label' => $formatPct((float) ($plot['share_percent'] ?? 0)).'%',
                    ];
                })->values()->all(),
            ];
        })->values()->all();

        $activeMarlaPerAcre = (float) ($activePayload['marla_per_acre'] ?? $collectiveSummary['marla_per_acre'] ?? 160);
        $activeExemption = [
            'summary' => (string) ($collectiveSummary['exemption_summary'] ?? '—'),
            'marla_per_acre' => $activeMarlaPerAcre,
            'marla_label' => '1 acre = '.$formatNum($activeMarlaPerAcre).'M',
            'components' => $activeComponents,
            'has_details' => $activeComponents !== [],
        ];

        $activeExemptionConfig = SaleExemptionConfig::fromSnapshotData(
            $project,
            is_array($activePayload) ? $activePayload : []
        );
        $activeFileCalculator = SaleExemptionFileCalculator::calculate(
            (float) ($collectiveSummary['total_land_marla'] ?? $collective->total_land_marla ?? 0),
            $activeExemptionConfig
        );

        return view('sales.files.collective-show', [
            'project' => $project,
            'collectiveModel' => $collective,
            'collective' => $collectiveSummary,
            'exemptionOptions' => $exemptionOptions,
            'activeExemption' => $activeExemption,
            'activeFileCalculator' => $activeFileCalculator,
        ]);
    }

    public function storeCollective(Request $request, Project $project, FileSaleLandService $fileSaleLandService)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'sale_land_ids' => ['required', 'array', 'min:1'],
            'sale_land_ids.*' => ['required', 'integer', 'distinct'],
        ]);

        $result = $fileSaleLandService->saveAsSaleFile(
            $project,
            $validated['sale_land_ids'],
            $validated['name'],
        );

        return redirect()
            ->route('sale.files.index', $project)
            ->with('success', 'Sale file "'.$result['collective']['name'].'" saved with '.$result['file_count'].' file line(s) and exemption formula.');
    }

    public function groupCollective(Request $request, Project $project, FileSaleLandService $fileSaleLandService)
    {
        $validated = $request->validate([
            'sale_land_ids' => ['required', 'array', 'min:1'],
            'sale_land_ids.*' => ['required', 'integer', 'distinct'],
            'placement' => ['required', 'string', Rule::in([
                FileSaleLandService::PLACEMENT_NEW_COLLECTIVE,
                FileSaleLandService::PLACEMENT_EXISTING_COLLECTIVE,
            ])],
            'collective_id' => ['nullable', 'integer'],
            'name' => ['nullable', 'string', 'max:150'],
        ]);

        $result = $fileSaleLandService->groupIntoCollective(
            $project,
            $validated['sale_land_ids'],
            $validated['placement'],
            isset($validated['collective_id']) ? (int) $validated['collective_id'] : null,
            isset($validated['name']) ? trim((string) $validated['name']) : null,
        );

        return redirect()
            ->route('sale.files.index', $project)
            ->with('success', $result['file_count'].' file(s) saved under '.$result['collective']['name'].'.');
    }

    public function completeCollective(Project $project, FileSaleCollective $collective, FileSaleLandService $fileSaleLandService)
    {
        abort_unless((int) $collective->project_id === (int) $project->id, 404);

        $fileSaleLandService->completeCollective($project, $collective);

        return redirect()
            ->route('sale.files.collectives.show', [$project, $collective])
            ->with('success', $collective->name.' marked complete. No more files can be added.');
    }

    public function reopenCollective(Project $project, FileSaleCollective $collective, FileSaleLandService $fileSaleLandService)
    {
        abort_unless((int) $collective->project_id === (int) $project->id, 404);

        $fileSaleLandService->reopenCollective($project, $collective);

        return redirect()
            ->route('sale.files.collectives.show', [$project, $collective])
            ->with('success', $collective->name.' reopened. Files can be added again.');
    }

    public function applyExemption(Request $request, Project $project, FileSaleCollective $collective, FileSaleLandService $fileSaleLandService)
    {
        abort_unless((int) $collective->project_id === (int) $project->id, 404);

        $validated = $request->validate([
            'snapshot_id' => ['nullable', 'integer'],
        ]);

        $fileSaleLandService->applyExemptionToCollective(
            $project,
            $collective,
            ! empty($validated['snapshot_id']) ? (int) $validated['snapshot_id'] : null,
        );

        return redirect()
            ->route('sale.files.collectives.show', [$project, $collective])
            ->with('success', 'Exemption re-applied to '.$collective->name.'.');
    }
}
