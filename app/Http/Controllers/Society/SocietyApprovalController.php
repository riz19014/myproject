<?php

namespace App\Http\Controllers\Society;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Society\Concerns\InteractsWithSocietyProject;
use App\Models\Project;
use App\Models\SocietyApprovalRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SocietyApprovalController extends Controller
{
    use InteractsWithSocietyProject;

    public function index(Project $project): View
    {
        $project = $this->societyProject($project);
        $project->load('landType');

        $requests = SocietyApprovalRequest::query()
            ->where('project_id', $project->id)
            ->with(['requester', 'reviewer'])
            ->latest()
            ->paginate(30);

        return view('society.approvals.index', [
            'project' => $project,
            'requests' => $requests,
        ]);
    }

    public function store(Request $request, Project $project): RedirectResponse
    {
        $project = $this->societyProject($project);
        $this->authorizeSocietyManage();

        $validated = $request->validate([
            'module' => ['required', 'string', 'max:60'],
            'action' => ['required', 'string', 'max:60'],
            'subject_type' => ['required', 'string', 'max:120'],
            'subject_id' => ['required', 'integer', 'min:1'],
            'request_notes' => ['nullable', 'string', 'max:2000'],
        ]);

        SocietyApprovalRequest::query()->create([
            'project_id' => $project->id,
            'module' => $validated['module'],
            'action' => $validated['action'],
            'subject_type' => $validated['subject_type'],
            'subject_id' => $validated['subject_id'],
            'status' => SocietyApprovalRequest::STATUS_PENDING,
            'requested_by' => auth()->id(),
            'request_notes' => $validated['request_notes'] ?? null,
        ]);

        return back()->with('success', 'Approval request submitted.');
    }

    public function review(Request $request, Project $project, SocietyApprovalRequest $approval): RedirectResponse
    {
        $project = $this->societyProject($project);
        $this->authorizeSocietyApprove();
        abort_unless((int) $approval->project_id === (int) $project->id, 404);
        abort_unless($approval->isPending(), 422, 'Already reviewed.');

        $validated = $request->validate([
            'decision' => ['required', 'in:approved,rejected'],
            'review_notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $approval->update([
            'status' => $validated['decision'] === 'approved'
                ? SocietyApprovalRequest::STATUS_APPROVED
                : SocietyApprovalRequest::STATUS_REJECTED,
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
            'review_notes' => $validated['review_notes'] ?? null,
        ]);

        return back()->with('success', 'Approval '.$validated['decision'].'.');
    }
}
