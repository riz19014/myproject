<?php

namespace App\Http\Controllers\Society;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Society\Concerns\InteractsWithSocietyProject;
use App\Models\BoqItem;
use App\Models\DayBookEntry;
use App\Models\DevelopmentPackage;
use App\Models\DevelopmentProgressLog;
use App\Models\Party;
use App\Models\Project;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SocietyDevelopmentController extends Controller
{
    use InteractsWithSocietyProject;

    public function index(Project $project): View
    {
        $project = $this->societyProject($project);
        $project->load('landType');

        $packages = DevelopmentPackage::query()
            ->where('project_id', $project->id)
            ->with([
                'contractor',
                'boqItems',
                'progressLogs' => fn ($q) => $q->orderByDesc('logged_on')->orderByDesc('id')->limit(8),
            ])
            ->orderBy('name')
            ->get();

        $contractorPayments = DayBookEntry::query()
            ->where('project_id', $project->id)
            ->where('type', DayBookEntry::TYPE_CASH_OUT)
            ->whereNotNull('paid_by_party_id')
            ->selectRaw('paid_by_party_id, SUM(amount) as total')
            ->groupBy('paid_by_party_id')
            ->pluck('total', 'paid_by_party_id');

        $parties = Party::query()->orderBy('name')->get(['id', 'name']);

        return view('society.development.index', [
            'project' => $project,
            'packages' => $packages,
            'parties' => $parties,
            'types' => DevelopmentPackage::TYPES,
            'statuses' => DevelopmentPackage::STATUSES,
            'contractorPayments' => $contractorPayments,
        ]);
    }

    public function storePackage(Request $request, Project $project): RedirectResponse
    {
        $project = $this->societyProject($project);
        $this->authorizeSocietyManage();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:160'],
            'package_type' => ['required', 'string', 'in:'.implode(',', array_keys(DevelopmentPackage::TYPES))],
            'contractor_party_id' => ['nullable', 'integer', 'exists:parties,id'],
            'estimated_amount' => ['nullable', 'numeric', 'min:0'],
            'status' => ['nullable', 'string', 'in:'.implode(',', array_keys(DevelopmentPackage::STATUSES))],
            'start_date' => ['nullable', 'date'],
            'target_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        DevelopmentPackage::query()->create([
            'project_id' => $project->id,
            'name' => $validated['name'],
            'package_type' => $validated['package_type'],
            'contractor_party_id' => $validated['contractor_party_id'] ?? null,
            'estimated_amount' => $validated['estimated_amount'] ?? 0,
            'status' => $validated['status'] ?? 'planned',
            'start_date' => $validated['start_date'] ?? null,
            'target_date' => $validated['target_date'] ?? null,
            'notes' => $validated['notes'] ?? null,
        ]);

        return back()->with('success', 'Development package created.');
    }

    public function storeBoq(Request $request, Project $project, DevelopmentPackage $package): RedirectResponse
    {
        $project = $this->societyProject($project);
        $this->authorizeSocietyManage();
        abort_unless((int) $package->project_id === (int) $project->id, 404);

        $validated = $request->validate([
            'item_name' => ['required', 'string', 'max:160'],
            'unit' => ['nullable', 'string', 'max:40'],
            'quantity' => ['required', 'numeric', 'min:0'],
            'unit_rate' => ['required', 'numeric', 'min:0'],
            'actual_amount' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $item = new BoqItem([
            'development_package_id' => $package->id,
            'item_name' => $validated['item_name'],
            'unit' => $validated['unit'] ?? null,
            'quantity' => $validated['quantity'],
            'unit_rate' => $validated['unit_rate'],
            'actual_amount' => $validated['actual_amount'] ?? 0,
            'notes' => $validated['notes'] ?? null,
            'sort_order' => (int) $package->boqItems()->max('sort_order') + 1,
        ]);
        $item->recalculateEstimated();
        $item->save();

        $package->update([
            'estimated_amount' => $package->boqItems()->sum('estimated_amount'),
        ]);

        return back()->with('success', 'BOQ item added.');
    }

    public function logProgress(Request $request, Project $project, DevelopmentPackage $package): RedirectResponse
    {
        $project = $this->societyProject($project);
        $this->authorizeSocietyManage();
        abort_unless((int) $package->project_id === (int) $project->id, 404);

        $validated = $request->validate([
            'progress_percent' => ['required', 'numeric', 'min:0', 'max:100'],
            'logged_on' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'status' => ['nullable', 'string', 'in:'.implode(',', array_keys(DevelopmentPackage::STATUSES))],
        ]);

        DevelopmentProgressLog::query()->create([
            'development_package_id' => $package->id,
            'user_id' => auth()->id(),
            'progress_percent' => $validated['progress_percent'],
            'logged_on' => $validated['logged_on'],
            'notes' => $validated['notes'] ?? null,
        ]);

        $package->progress_percent = $validated['progress_percent'];
        if (! empty($validated['status'])) {
            $package->status = $validated['status'];
        } elseif ((float) $validated['progress_percent'] >= 100) {
            $package->status = 'completed';
        } elseif ((float) $validated['progress_percent'] > 0) {
            $package->status = 'in_progress';
        }
        $package->save();

        return back()->with('success', 'Progress logged.');
    }

    public function destroyPackage(Project $project, DevelopmentPackage $package): RedirectResponse
    {
        $project = $this->societyProject($project);
        $this->authorizeSocietyManage();
        abort_unless((int) $package->project_id === (int) $project->id, 404);
        $package->delete();

        return back()->with('success', 'Package deleted.');
    }
}
