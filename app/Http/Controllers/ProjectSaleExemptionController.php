<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Support\ProjectExemptionDefaults;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProjectSaleExemptionController extends Controller
{
    public function edit(Project $project)
    {
        ProjectExemptionDefaults::ensureForProject($project);
        $project->load([
            'saleExemptionComponents.plotTypes' => fn ($q) => $q->orderBy('sort_order'),
        ]);

        return view('sales.exemption-config', compact('project'));
    }

    public function update(Request $request, Project $project)
    {
        $validated = $request->validate([
            'marla_per_acre' => ['required', 'numeric', 'min:1', 'max:1000', 'regex:/^\d+(\.\d{1,4})?$/'],
            'components' => ['required', 'array', 'min:1'],
            'components.*.id' => ['nullable', 'integer'],
            'components.*.slug' => ['required', 'string', 'max:40', 'regex:/^[a-z0-9_]+$/'],
            'components.*.label' => ['required', 'string', 'max:100'],
            'components.*.pool_percent' => ['required', 'numeric', 'min:0', 'max:100', 'regex:/^\d+(\.\d{1,4})?$/'],
            'components.*.plot_types' => ['required', 'array', 'min:1'],
            'components.*.plot_types.*.id' => ['nullable', 'integer'],
            'components.*.plot_types.*.slug' => ['required', 'string', 'max:40', 'regex:/^[a-z0-9_]+$/'],
            'components.*.plot_types.*.label' => ['required', 'string', 'max:100'],
            'components.*.plot_types.*.marla_per_plot' => ['required', 'numeric', 'min:0', 'regex:/^\d+(\.\d{1,4})?$/'],
            'components.*.plot_types.*.nominal_marla' => ['required', 'numeric', 'min:0.0001', 'regex:/^\d+(\.\d{1,4})?$/'],
            'components.*.plot_types.*.share_percent' => ['required', 'numeric', 'min:0', 'max:100', 'regex:/^\d+(\.\d{1,4})?$/'],
        ], [
            'components.*.slug.regex' => 'Component code: lowercase letters, numbers, underscores only.',
            'components.*.plot_types.*.slug.regex' => 'Plot code: lowercase letters, numbers, underscores only.',
        ]);

        $marlaPerAcre = round((float) $validated['marla_per_acre'], 4);
        $project->update(['marla_per_acre' => $marlaPerAcre]);

        $submittedComponentIds = [];

        DB::transaction(function () use ($project, $validated, $marlaPerAcre, &$submittedComponentIds) {
            foreach ($validated['components'] as $i => $row) {
                $slug = strtolower(trim($row['slug']));
                $component = null;
                if (! empty($row['id'])) {
                    $component = $project->saleExemptionComponents()->whereKey($row['id'])->first();
                }
                if (! $component) {
                    $component = $project->saleExemptionComponents()->where('slug', $slug)->first();
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
                foreach ($row['plot_types'] as $j => $plotRow) {
                    $plotSlug = strtolower(trim($plotRow['slug']));
                    $plot = null;
                    if (! empty($plotRow['id'])) {
                        $plot = $component->plotTypes()->whereKey($plotRow['id'])->first();
                    }
                    if (! $plot) {
                        $plot = $component->plotTypes()->where('slug', $plotSlug)->first();
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

        return redirect()
            ->route('sale.projects.exemption.edit', $project)
            ->with('success', 'Exemption configuration saved for this project.');
    }
}
