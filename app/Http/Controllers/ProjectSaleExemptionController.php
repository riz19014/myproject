<?php

namespace App\Http\Controllers;

use App\Models\FileSaleCollective;
use App\Models\Project;
use App\Models\ProjectSaleExemptionSnapshot;
use App\Support\FileSaleLandService;
use App\Support\LandMeasure;
use App\Support\ProjectExemptionDefaults;
use App\Support\SaleExemptionConfig;
use App\Support\SaleExemptionFileCalculator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ProjectSaleExemptionController extends Controller
{
    public function index(Request $request)
    {
        $trialAcre = max(0, (int) $request->input('area_acre', 1));
        $trialKanal = max(0, (int) $request->input('area_kanal', 0));
        $trialMarlaPart = max(0, (int) $request->input('area_marla', 0));
        $trialSqft = max(0, (int) $request->input('area_sqft', 0));

        if ($trialAcre + $trialKanal + $trialMarlaPart + $trialSqft <= 0) {
            $trialAcre = 1;
        }

        $trialTotalMarla = LandMeasure::marlaFromAkms($trialAcre, $trialKanal, $trialMarlaPart, $trialSqft);
        $trialLandLabel = LandMeasure::formatAkmsLabelFromMarla($trialTotalMarla);

        $projects = Project::query()
            ->where('is_dha', true)
            ->with([
                'landType',
                'saleExemptionSnapshots',
            ])
            ->withCount(['projectFiles', 'saleExemptionSnapshots'])
            ->orderBy('name')
            ->get();

        $snapshotTrials = [];
        foreach ($projects as $project) {
            foreach ($project->saleExemptionSnapshots as $snapshot) {
                $config = SaleExemptionConfig::fromSnapshotData($project, $snapshot->payload);
                $snapshotTrials[$snapshot->id] = SaleExemptionFileCalculator::calculate($trialTotalMarla, $config);
            }
        }

        return view('sales.exemption-index', [
            'projects' => $projects,
            'trialAcre' => $trialAcre,
            'trialKanal' => $trialKanal,
            'trialMarlaPart' => $trialMarlaPart,
            'trialSqft' => $trialSqft,
            'trialTotalMarla' => $trialTotalMarla,
            'trialLandLabel' => $trialLandLabel,
            'snapshotTrials' => $snapshotTrials,
        ]);
    }

    public function edit(Request $request, Project $project)
    {
        abort_unless($project->isDha(), 404);

        ProjectExemptionDefaults::ensureForProject($project);
        $project->load([
            'saleExemptionComponents.plotTypes' => fn ($q) => $q->orderBy('sort_order'),
        ]);

        $trialAcre = max(0, (int) $request->input('area_acre', 1));
        $trialKanal = max(0, (int) $request->input('area_kanal', 0));
        $trialMarlaPart = max(0, (int) $request->input('area_marla', 0));
        $trialSqft = max(0, (int) $request->input('area_sqft', 0));
        if ($trialAcre + $trialKanal + $trialMarlaPart + $trialSqft <= 0) {
            $trialAcre = 1;
        }
        $trialTotalMarla = LandMeasure::marlaFromAkms($trialAcre, $trialKanal, $trialMarlaPart, $trialSqft);
        $trialLandLabel = LandMeasure::formatAkmsLabelFromMarla($trialTotalMarla);
        $fileCalculator = SaleExemptionFileCalculator::calculate(
            $trialTotalMarla,
            SaleExemptionConfig::forProject($project)
        );

        $returnCollectiveId = $request->integer('return_collective_id') ?: null;
        if ($returnCollectiveId) {
            $belongs = FileSaleCollective::query()
                ->where('project_id', $project->id)
                ->whereKey($returnCollectiveId)
                ->exists();
            if (! $belongs) {
                $returnCollectiveId = null;
            }
        }

        return view('sales.exemption-config', compact(
            'project',
            'trialAcre',
            'trialKanal',
            'trialMarlaPart',
            'trialSqft',
            'trialTotalMarla',
            'trialLandLabel',
            'fileCalculator',
        ) + [
            'snapshot' => null,
            'formComponents' => null,
            'formMarlaPerAcre' => null,
            'returnCollectiveId' => $returnCollectiveId,
        ]);
    }

    public function editSnapshot(Request $request, Project $project, ProjectSaleExemptionSnapshot $snapshot)
    {
        abort_unless($project->isDha(), 404);
        abort_unless((int) $snapshot->project_id === (int) $project->id, 404);

        $trialAcre = max(0, (int) $request->input('area_acre', 1));
        $trialKanal = max(0, (int) $request->input('area_kanal', 0));
        $trialMarlaPart = max(0, (int) $request->input('area_marla', 0));
        $trialSqft = max(0, (int) $request->input('area_sqft', 0));
        if ($trialAcre + $trialKanal + $trialMarlaPart + $trialSqft <= 0) {
            $trialAcre = 1;
        }
        $trialTotalMarla = LandMeasure::marlaFromAkms($trialAcre, $trialKanal, $trialMarlaPart, $trialSqft);
        $trialLandLabel = LandMeasure::formatAkmsLabelFromMarla($trialTotalMarla);
        $fileCalculator = SaleExemptionFileCalculator::calculate(
            $trialTotalMarla,
            SaleExemptionConfig::fromSnapshotData($project, $snapshot->payload)
        );

        return view('sales.exemption-config', [
            'project' => $project,
            'snapshot' => $snapshot,
            'formComponents' => $snapshot->components(),
            'formMarlaPerAcre' => $snapshot->marlaPerAcre(),
            'trialAcre' => $trialAcre,
            'trialKanal' => $trialKanal,
            'trialMarlaPart' => $trialMarlaPart,
            'trialSqft' => $trialSqft,
            'trialTotalMarla' => $trialTotalMarla,
            'trialLandLabel' => $trialLandLabel,
            'fileCalculator' => $fileCalculator,
        ]);
    }

    public function updateSnapshot(Request $request, Project $project, ProjectSaleExemptionSnapshot $snapshot)
    {
        abort_unless($project->isDha(), 404);
        abort_unless((int) $snapshot->project_id === (int) $project->id, 404);

        $validated = $this->validateExemptionForm($request);
        $payload = $this->payloadFromValidated($validated);

        $snapshot->update(['payload' => $payload]);

        return redirect()
            ->route('sale.exemption.index')
            ->with('success', 'Saved exemption updated for '.$project->name.'.');
    }

    public function update(Request $request, Project $project)
    {
        abort_unless($project->isDha(), 404);

        $validated = $this->validateExemptionForm($request);

        $marlaPerAcre = round((float) $validated['marla_per_acre'], 4);
        $project->update(['marla_per_acre' => $marlaPerAcre]);

        $submittedComponentIds = [];
        $seenComponentSlugs = [];

        DB::transaction(function () use ($project, $validated, $marlaPerAcre, &$submittedComponentIds, &$seenComponentSlugs) {
            foreach ($validated['components'] as $i => $row) {
                $slug = strtolower(trim($row['slug']));
                if (isset($seenComponentSlugs[$slug])) {
                    throw ValidationException::withMessages([
                        "components.{$i}.slug" => ['Each allocation category code must be unique in this form.'],
                    ]);
                }
                $seenComponentSlugs[$slug] = true;

                $componentId = ! empty($row['id']) ? (int) $row['id'] : null;
                $component = $componentId
                    ? $project->saleExemptionComponents()->whereKey($componentId)->first()
                    : null;

                if ($componentId && ! $component) {
                    throw ValidationException::withMessages([
                        "components.{$i}.id" => ['Allocation category not found for this project.'],
                    ]);
                }

                $slugTaken = $project->saleExemptionComponents()
                    ->where('slug', $slug)
                    ->when($component, fn ($q) => $q->where('id', '!=', $component->id))
                    ->exists();
                if ($slugTaken) {
                    throw ValidationException::withMessages([
                        "components.{$i}.slug" => ['This category code already exists for this project. Use a different code so the previous exemption is kept.'],
                    ]);
                }

                if ($component) {
                    $component->update([
                        'slug' => $slug,
                        'label' => trim($row['label']),
                        'pool_percent' => round((float) $row['pool_percent'], 4),
                        'marla_per_acre' => $marlaPerAcre,
                        'sort_order' => $i,
                    ]);
                } else {
                    $component = $project->saleExemptionComponents()->create([
                        'slug' => $slug,
                        'label' => trim($row['label']),
                        'pool_percent' => round((float) $row['pool_percent'], 4),
                        'marla_per_acre' => $marlaPerAcre,
                        'sort_order' => $i,
                    ]);
                }
                $submittedComponentIds[] = $component->id;

                $plotIds = [];
                $seenPlotSlugs = [];
                foreach ($row['plot_types'] as $j => $plotRow) {
                    $plotSlug = strtolower(trim($plotRow['slug']));
                    if (isset($seenPlotSlugs[$plotSlug])) {
                        throw ValidationException::withMessages([
                            "components.{$i}.plot_types.{$j}.slug" => ['Each plot code must be unique within this category.'],
                        ]);
                    }
                    $seenPlotSlugs[$plotSlug] = true;

                    $plotId = ! empty($plotRow['id']) ? (int) $plotRow['id'] : null;
                    $plot = $plotId
                        ? $component->plotTypes()->whereKey($plotId)->first()
                        : null;

                    if ($plotId && ! $plot) {
                        throw ValidationException::withMessages([
                            "components.{$i}.plot_types.{$j}.id" => ['Plot type not found for this category.'],
                        ]);
                    }

                    $plotSlugTaken = $component->plotTypes()
                        ->where('slug', $plotSlug)
                        ->when($plot, fn ($q) => $q->where('id', '!=', $plot->id))
                        ->exists();
                    if ($plotSlugTaken) {
                        throw ValidationException::withMessages([
                            "components.{$i}.plot_types.{$j}.slug" => ['This plot code already exists in this category. Use a different code so the previous plot rule is kept.'],
                        ]);
                    }

                    $attrs = [
                        'slug' => $plotSlug,
                        'label' => trim($plotRow['label']),
                        'marla_per_plot' => round((float) $plotRow['marla_per_plot'], 4),
                        'nominal_marla' => round((float) $plotRow['nominal_marla'], 4),
                        'share_percent' => round((float) $plotRow['share_percent'], 4),
                        'sort_order' => $j,
                    ];
                    if ($plot) {
                        $plot->update($attrs);
                    } else {
                        $plot = $component->plotTypes()->create(array_merge($attrs, [
                            'project_id' => $project->id,
                        ]));
                    }
                    $plotIds[] = $plot->id;
                }
                $component->plotTypes()->whereNotIn('id', $plotIds)->delete();
            }

            $project->saleExemptionComponents()->whereNotIn('id', $submittedComponentIds)->delete();
        });

        $project->refresh();
        $snapshot = ProjectSaleExemptionSnapshot::storeFromProject($project);

        $returnCollectiveId = $request->integer('return_collective_id') ?: null;
        if ($returnCollectiveId) {
            $collective = FileSaleCollective::query()
                ->where('project_id', $project->id)
                ->whereKey($returnCollectiveId)
                ->first();

            if ($collective) {
                app(FileSaleLandService::class)->applyExemptionToCollective(
                    $project,
                    $collective,
                    (int) $snapshot->id,
                );

                return redirect()
                    ->route('sale.files.collectives.show', [$project, $collective])
                    ->with('success', 'Exemption saved and applied to '.$collective->name.'.');
            }
        }

        return redirect()
            ->route('sale.exemption.index')
            ->with('success', 'Exemption configuration saved and added to the list for '.$project->name.'.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validateExemptionForm(Request $request): array
    {
        $this->normalizeExemptionRequest($request);

        return $request->validate([
            'marla_per_acre' => ['required', 'numeric', 'min:1', 'max:1000', 'decimal:0,4'],
            'components' => ['required', 'array', 'min:1'],
            'components.*.id' => ['nullable', 'integer'],
            'components.*.slug' => ['required', 'string', 'max:40', 'regex:/^[a-z0-9_]+$/'],
            'components.*.label' => ['required', 'string', 'max:100'],
            'components.*.pool_percent' => ['required', 'numeric', 'min:0', 'max:100', 'decimal:0,4'],
            'components.*.plot_types' => ['required', 'array', 'min:1'],
            'components.*.plot_types.*.id' => ['nullable', 'integer'],
            'components.*.plot_types.*.slug' => ['required', 'string', 'max:40', 'regex:/^[a-z0-9_]+$/'],
            'components.*.plot_types.*.label' => ['required', 'string', 'max:100'],
            'components.*.plot_types.*.marla_per_plot' => ['required', 'numeric', 'min:0', 'decimal:0,4'],
            'components.*.plot_types.*.nominal_marla' => ['required', 'numeric', 'min:0.0001', 'decimal:0,4'],
            'components.*.plot_types.*.share_percent' => ['required', 'numeric', 'min:0', 'max:100', 'decimal:0,4'],
        ], [
            'components.*.slug.regex' => 'Component code: lowercase letters, numbers, underscores only.',
            'components.*.plot_types.*.slug.regex' => 'Plot code: lowercase letters, numbers, underscores only.',
            'components.*.pool_percent.decimal' => 'Pool % may have up to 4 decimal places (e.g. 25 or 2.5).',
            'components.*.plot_types.*.marla_per_plot.decimal' => 'Marla per plot may have up to 4 decimal places.',
            'components.*.plot_types.*.nominal_marla.decimal' => 'Nominal marla may have up to 4 decimal places.',
            'components.*.plot_types.*.share_percent.decimal' => 'Share % may have up to 4 decimal places.',
        ]);
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array{marla_per_acre: float, components: list<array<string, mixed>>}
     */
    private function payloadFromValidated(array $validated): array
    {
        $seenComponentSlugs = [];
        $components = [];

        foreach ($validated['components'] as $i => $row) {
            $slug = strtolower(trim($row['slug']));
            if (isset($seenComponentSlugs[$slug])) {
                throw ValidationException::withMessages([
                    "components.{$i}.slug" => ['Each allocation category code must be unique in this form.'],
                ]);
            }
            $seenComponentSlugs[$slug] = true;

            $seenPlotSlugs = [];
            $plotTypes = [];
            foreach ($row['plot_types'] as $j => $plotRow) {
                $plotSlug = strtolower(trim($plotRow['slug']));
                if (isset($seenPlotSlugs[$plotSlug])) {
                    throw ValidationException::withMessages([
                        "components.{$i}.plot_types.{$j}.slug" => ['Each plot code must be unique within this category.'],
                    ]);
                }
                $seenPlotSlugs[$plotSlug] = true;
                $plotTypes[] = [
                    'slug' => $plotSlug,
                    'label' => trim($plotRow['label']),
                    'marla_per_plot' => round((float) $plotRow['marla_per_plot'], 4),
                    'nominal_marla' => round((float) $plotRow['nominal_marla'], 4),
                    'share_percent' => round((float) $plotRow['share_percent'], 4),
                ];
            }

            $components[] = [
                'slug' => $slug,
                'label' => trim($row['label']),
                'pool_percent' => round((float) $row['pool_percent'], 4),
                'plot_types' => $plotTypes,
            ];
        }

        return [
            'marla_per_acre' => round((float) $validated['marla_per_acre'], 4),
            'components' => $components,
        ];
    }

    private function normalizeExemptionRequest(Request $request): void
    {
        $components = collect($request->input('components', []))
            ->map(function (array $component) {
                $component['pool_percent'] = $this->normalizeDecimalInput($component['pool_percent'] ?? null);
                $component['plot_types'] = collect($component['plot_types'] ?? [])
                    ->map(function (array $plot) {
                        $plot['marla_per_plot'] = $this->normalizeDecimalInput($plot['marla_per_plot'] ?? null);
                        $plot['nominal_marla'] = $this->normalizeDecimalInput($plot['nominal_marla'] ?? null);
                        $plot['share_percent'] = $this->normalizeDecimalInput($plot['share_percent'] ?? null);

                        return $plot;
                    })
                    ->values()
                    ->all();

                return $component;
            })
            ->values()
            ->all();

        $request->merge([
            'marla_per_acre' => $this->normalizeDecimalInput($request->input('marla_per_acre')),
            'components' => $components,
        ]);
    }

    private function normalizeDecimalInput(mixed $value): mixed
    {
        if ($value === null || $value === '') {
            return $value;
        }

        if (is_string($value)) {
            $value = str_replace(',', '', trim($value));
            $value = rtrim($value, '%');
        }

        if (! is_numeric($value)) {
            return $value;
        }

        return rtrim(rtrim(number_format(round((float) $value, 4), 4, '.', ''), '0'), '.') ?: '0';
    }
}
