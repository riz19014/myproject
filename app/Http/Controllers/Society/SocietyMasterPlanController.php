<?php

namespace App\Http\Controllers\Society;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Society\Concerns\InteractsWithSocietyProject;
use App\Models\Project;
use App\Models\ProjectLandAllocation;
use App\Support\LandMeasure;
use App\Support\SocietyProject;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SocietyMasterPlanController extends Controller
{
    use InteractsWithSocietyProject;

    public function index(Project $project): View
    {
        $project = $this->societyProject($project);
        $project->load('landType');

        $purchasedMarla = SocietyProject::purchasedMarla($project);
        $allocations = ProjectLandAllocation::query()
            ->where('project_id', $project->id)
            ->orderBy('id')
            ->get();

        $rows = [];
        if ($allocations->isEmpty()) {
            foreach (ProjectLandAllocation::CATEGORIES as $key => $label) {
                $rows[] = [
                    'id' => null,
                    'category' => $key,
                    'label' => $label,
                    'area_acre' => 0,
                    'area_kanal' => 0,
                    'area_marla' => 0,
                    'area_sqft' => 0,
                    'land_area_marla' => 0.0,
                    'notes' => null,
                ];
            }
        } else {
            foreach ($allocations as $row) {
                $rows[] = [
                    'id' => $row->id,
                    'category' => $row->category,
                    'label' => $row->categoryLabel(),
                    'area_acre' => (int) $row->area_acre,
                    'area_kanal' => (int) $row->area_kanal,
                    'area_marla' => (int) $row->area_marla,
                    'area_sqft' => (int) $row->area_sqft,
                    'land_area_marla' => (float) $row->land_area_marla,
                    'notes' => $row->notes,
                ];
            }
        }

        $allocatedMarla = collect($rows)->sum('land_area_marla');
        $variance = $purchasedMarla - $allocatedMarla;

        return view('society.master-plan.index', [
            'project' => $project,
            'rows' => $rows,
            'purchasedMarla' => $purchasedMarla,
            'purchasedLabel' => LandMeasure::formatAkmsLabelFromMarla($purchasedMarla),
            'allocatedMarla' => $allocatedMarla,
            'allocatedLabel' => LandMeasure::formatAkmsLabelFromMarla($allocatedMarla),
            'variance' => $variance,
            'varianceLabel' => LandMeasure::formatAkmsLabelFromMarla(abs($variance)),
            'reconciled' => abs($variance) < 0.01,
        ]);
    }

    public function update(Request $request, Project $project): RedirectResponse
    {
        $project = $this->societyProject($project);
        $this->authorizeSocietyManage();

        $validated = $request->validate([
            'rows' => ['nullable', 'array'],
            'rows.*.id' => [
                'nullable',
                'integer',
                Rule::exists('project_land_allocations', 'id')->where(fn ($q) => $q->where('project_id', $project->id)),
            ],
            'rows.*.category' => ['nullable', 'string', 'max:40'],
            'rows.*.name' => ['required', 'string', 'max:120'],
            'rows.*.area_acre' => ['nullable', 'integer', 'min:0'],
            'rows.*.area_kanal' => ['nullable', 'integer', 'min:0'],
            'rows.*.area_marla' => ['nullable', 'integer', 'min:0'],
            'rows.*.area_sqft' => ['nullable', 'integer', 'min:0'],
        ]);

        $rows = array_values($validated['rows'] ?? []);

        DB::transaction(function () use ($project, $rows) {
            $keptIds = [];

            foreach ($rows as $row) {
                $name = trim((string) $row['name']);
                $id = ! empty($row['id']) ? (int) $row['id'] : null;

                if ($id) {
                    $allocation = ProjectLandAllocation::query()
                        ->where('project_id', $project->id)
                        ->whereKey($id)
                        ->firstOrFail();
                } else {
                    $category = trim((string) ($row['category'] ?? ''));
                    if ($category === '' || ! $this->categoryKeyAvailable($project, $category)) {
                        $category = $this->uniqueCategoryKey($project, $name);
                    }
                    $allocation = ProjectLandAllocation::query()->firstOrNew([
                        'project_id' => $project->id,
                        'category' => $category,
                    ]);
                }

                $allocation->name = $name;
                $allocation->area_acre = (int) ($row['area_acre'] ?? 0);
                $allocation->area_kanal = (int) ($row['area_kanal'] ?? 0);
                $allocation->area_marla = (int) ($row['area_marla'] ?? 0);
                $allocation->area_sqft = (int) ($row['area_sqft'] ?? 0);
                $allocation->syncMarlaFromAkms();
                $allocation->save();

                $keptIds[] = (int) $allocation->id;
            }

            ProjectLandAllocation::query()
                ->where('project_id', $project->id)
                ->when($keptIds !== [], fn ($q) => $q->whereNotIn('id', $keptIds), fn ($q) => $q)
                ->delete();
        });

        return redirect()
            ->route('society.master-plan', $project)
            ->with('success', 'Master plan allocations saved.');
    }

    private function categoryKeyAvailable(Project $project, string $category, ?int $ignoreId = null): bool
    {
        return ! ProjectLandAllocation::query()
            ->where('project_id', $project->id)
            ->where('category', $category)
            ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->exists();
    }

    private function uniqueCategoryKey(Project $project, string $name): string
    {
        $base = Str::slug($name);
        if ($base === '') {
            $base = 'category';
        }
        $base = Str::limit($base, 30, '');
        $key = $base;
        $i = 2;
        while (! $this->categoryKeyAvailable($project, $key)) {
            $key = Str::limit($base, 28, '').'-'.$i;
            $i++;
        }

        return $key;
    }
}
