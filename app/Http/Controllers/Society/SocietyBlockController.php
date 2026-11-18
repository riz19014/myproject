<?php

namespace App\Http\Controllers\Society;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Society\Concerns\InteractsWithSocietyProject;
use App\Models\Project;
use App\Models\ProjectBlock;
use App\Models\ProjectLandAllocation;
use App\Models\ProjectPlot;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SocietyBlockController extends Controller
{
    use InteractsWithSocietyProject;

    public function index(Project $project): View
    {
        $project = $this->societyProject($project);
        $project->load(['landType', 'blocks.plots']);

        $plots = ProjectPlot::query()
            ->where('project_id', $project->id)
            ->with('block')
            ->orderBy('plot_number')
            ->get();

        $statusCounts = $plots->groupBy('status')->map->count();

        return view('society.blocks.index', [
            'project' => $project,
            'blocks' => $project->blocks,
            'plots' => $plots,
            'statusCounts' => $statusCounts,
            'categories' => ProjectPlot::CATEGORIES,
            'statuses' => ProjectPlot::STATUSES,
            'allocationCategories' => ProjectLandAllocation::CATEGORIES,
        ]);
    }

    public function storeBlock(Request $request, Project $project): RedirectResponse
    {
        $project = $this->societyProject($project);
        $this->authorizeSocietyManage();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'sector' => ['nullable', 'string', 'max:120'],
            'category' => ['required', 'string', 'in:'.implode(',', array_keys(ProjectPlot::CATEGORIES))],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        ProjectBlock::query()->create([
            'project_id' => $project->id,
            'name' => $validated['name'],
            'sector' => $validated['sector'] ?? null,
            'category' => $validated['category'],
            'notes' => $validated['notes'] ?? null,
            'sort_order' => (int) ProjectBlock::query()->where('project_id', $project->id)->max('sort_order') + 1,
        ]);

        return back()->with('success', 'Block created.');
    }

    public function destroyBlock(Project $project, ProjectBlock $block): RedirectResponse
    {
        $project = $this->societyProject($project);
        $this->authorizeSocietyManage();
        abort_unless((int) $block->project_id === (int) $project->id, 404);

        if ($block->plots()->exists()) {
            return back()->with('error', 'Remove or reassign plots before deleting this block.');
        }

        $block->delete();

        return back()->with('success', 'Block deleted.');
    }

    public function storePlot(Request $request, Project $project): RedirectResponse
    {
        $project = $this->societyProject($project);
        $this->authorizeSocietyManage();

        $validated = $request->validate([
            'project_block_id' => ['nullable', 'integer', 'exists:project_blocks,id'],
            'plot_number' => ['required', 'string', 'max:60'],
            'category' => ['required', 'string', 'in:'.implode(',', array_keys(ProjectPlot::CATEGORIES))],
            'area_acre' => ['nullable', 'integer', 'min:0'],
            'area_kanal' => ['nullable', 'integer', 'min:0'],
            'area_marla' => ['nullable', 'integer', 'min:0'],
            'area_sqft' => ['nullable', 'integer', 'min:0'],
            'list_price' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        if (! empty($validated['project_block_id'])) {
            abort_unless(
                ProjectBlock::query()
                    ->whereKey($validated['project_block_id'])
                    ->where('project_id', $project->id)
                    ->exists(),
                404
            );
        }

        $plot = new ProjectPlot([
            'project_id' => $project->id,
            'project_block_id' => $validated['project_block_id'] ?? null,
            'plot_number' => $validated['plot_number'],
            'category' => $validated['category'],
            'area_acre' => (int) ($validated['area_acre'] ?? 0),
            'area_kanal' => (int) ($validated['area_kanal'] ?? 0),
            'area_marla' => (int) ($validated['area_marla'] ?? 0),
            'area_sqft' => (int) ($validated['area_sqft'] ?? 0),
            'list_price' => $validated['list_price'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'status' => ProjectPlot::STATUS_AVAILABLE,
        ]);
        $plot->syncMarlaFromAkms();
        $plot->save();

        return back()->with('success', 'Plot '.$plot->plot_number.' created.');
    }

    public function generatePlots(Request $request, Project $project): RedirectResponse
    {
        $project = $this->societyProject($project);
        $this->authorizeSocietyManage();

        $validated = $request->validate([
            'project_block_id' => ['required', 'integer', 'exists:project_blocks,id'],
            'prefix' => ['nullable', 'string', 'max:20'],
            'start_number' => ['required', 'integer', 'min:1'],
            'count' => ['required', 'integer', 'min:1', 'max:500'],
            'category' => ['required', 'string', 'in:'.implode(',', array_keys(ProjectPlot::CATEGORIES))],
            'area_acre' => ['nullable', 'integer', 'min:0'],
            'area_kanal' => ['nullable', 'integer', 'min:0'],
            'area_marla' => ['nullable', 'integer', 'min:0'],
            'area_sqft' => ['nullable', 'integer', 'min:0'],
            'list_price' => ['nullable', 'numeric', 'min:0'],
        ]);

        $block = ProjectBlock::query()
            ->whereKey($validated['project_block_id'])
            ->where('project_id', $project->id)
            ->firstOrFail();

        $prefix = (string) ($validated['prefix'] ?? '');
        $created = 0;
        for ($i = 0; $i < (int) $validated['count']; $i++) {
            $num = (int) $validated['start_number'] + $i;
            $plotNumber = $prefix.$num;
            if (ProjectPlot::query()->where('project_id', $project->id)->where('plot_number', $plotNumber)->exists()) {
                continue;
            }
            $plot = new ProjectPlot([
                'project_id' => $project->id,
                'project_block_id' => $block->id,
                'plot_number' => $plotNumber,
                'category' => $validated['category'],
                'area_acre' => (int) ($validated['area_acre'] ?? 0),
                'area_kanal' => (int) ($validated['area_kanal'] ?? 0),
                'area_marla' => (int) ($validated['area_marla'] ?? 0),
                'area_sqft' => (int) ($validated['area_sqft'] ?? 0),
                'list_price' => $validated['list_price'] ?? null,
                'status' => ProjectPlot::STATUS_AVAILABLE,
            ]);
            $plot->syncMarlaFromAkms();
            $plot->save();
            $created++;
        }

        return back()->with('success', $created.' plot(s) generated in '.$block->name.'.');
    }

    public function destroyPlot(Project $project, ProjectPlot $plot): RedirectResponse
    {
        $project = $this->societyProject($project);
        $this->authorizeSocietyManage();
        abort_unless((int) $plot->project_id === (int) $project->id, 404);

        if (! $plot->isAvailable()) {
            return back()->with('error', 'Only available plots can be deleted.');
        }

        $plot->delete();

        return back()->with('success', 'Plot deleted.');
    }
}
