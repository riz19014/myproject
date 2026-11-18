<?php

namespace App\Http\Controllers\Society;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Society\Concerns\InteractsWithSocietyProject;
use App\Models\DayBookEntry;
use App\Models\PartySubCategory;
use App\Models\Project;
use App\Models\ProjectBudget;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SocietyBudgetController extends Controller
{
    use InteractsWithSocietyProject;

    public function index(Project $project): View
    {
        $project = $this->societyProject($project);
        $project->load('landType');

        $budgets = ProjectBudget::query()
            ->where('project_id', $project->id)
            ->with('partySubCategory.category')
            ->orderBy('category')
            ->get();

        // Line Actual / mapped subcategory: non-land expense only
        $actualBySub = DayBookEntry::query()
            ->where('project_id', $project->id)
            ->where('type', DayBookEntry::TYPE_CASH_OUT)
            ->excludingLandAcquisition()
            ->whereNotNull('party_sub_category_id')
            ->selectRaw('party_sub_category_id, SUM(amount) as total')
            ->groupBy('party_sub_category_id')
            ->pluck('total', 'party_sub_category_id');

        $expenseLedger = DayBookEntry::query()
            ->where('project_id', $project->id)
            ->where('type', DayBookEntry::TYPE_CASH_OUT)
            ->with(['partySubCategory.category', 'paidByParty', 'purchaseFile'])
            ->orderBy('entry_date')
            ->orderBy('id')
            ->get();

        $totalOut = (float) $expenseLedger->sum('amount');
        $landAcquisitionOut = (float) $expenseLedger
            ->filter(fn (DayBookEntry $e) => $e->isLandAcquisitionPayment())
            ->sum('amount');
        $developmentExpenseOut = (float) ($totalOut - $landAcquisitionOut);

        $rows = $budgets->map(function (ProjectBudget $budget) use ($actualBySub) {
            $actual = 0.0;
            if ($budget->party_sub_category_id) {
                $actual = (float) ($actualBySub[$budget->party_sub_category_id] ?? 0);
            }

            return [
                'budget' => $budget,
                'actual' => $actual,
                'variance' => (float) $budget->estimated_amount - $actual,
            ];
        });

        $subCategories = PartySubCategory::query()
            ->with('category')
            ->orderBy('name')
            ->get();

        return view('society.budgets.index', [
            'project' => $project,
            'rows' => $rows,
            'subCategories' => $subCategories,
            'totalEstimated' => (float) $budgets->sum('estimated_amount'),
            'totalActualOut' => $totalOut,
            'landAcquisitionOut' => $landAcquisitionOut,
            'developmentExpenseOut' => $developmentExpenseOut,
            'expenseLedger' => $expenseLedger,
        ]);
    }

    public function store(Request $request, Project $project): RedirectResponse
    {
        $project = $this->societyProject($project);
        $this->authorizeSocietyManage();

        $validated = $request->validate([
            'category' => ['required', 'string', 'max:80'],
            'estimated_amount' => ['required', 'numeric', 'min:0'],
            'party_sub_category_id' => ['nullable', 'integer', 'exists:party_sub_categories,id'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        ProjectBudget::query()->updateOrCreate(
            [
                'project_id' => $project->id,
                'category' => $validated['category'],
            ],
            [
                'estimated_amount' => $validated['estimated_amount'],
                'party_sub_category_id' => $validated['party_sub_category_id'] ?? null,
                'notes' => $validated['notes'] ?? null,
            ]
        );

        return back()->with('success', 'Budget line saved.');
    }

    public function destroy(Project $project, ProjectBudget $budget): RedirectResponse
    {
        $project = $this->societyProject($project);
        $this->authorizeSocietyManage();
        abort_unless((int) $budget->project_id === (int) $project->id, 404);
        $budget->delete();

        return back()->with('success', 'Budget line removed.');
    }
}
