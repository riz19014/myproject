@extends('layouts.app')

@section('title', 'Approvals — '.$project->name)

@section('content')
@include('society.partials.nav')

<div class="mb-4">
    <h1 class="h3 mb-1">Approvals</h1>
    <p class="text-muted mb-0">Role-based approval workflow for society actions. Managers and admins can review.</p>
</div>

<div class="row g-4 mb-4">
    <div class="col-lg-4">
        <div class="card card-theme">
            <div class="card-body">
                <h5 class="mb-3">Request approval</h5>
                <form method="post" action="{{ route('society.approvals.store', $project) }}">
                    @csrf
                    <div class="mb-2">
                        <label class="form-label">Module</label>
                        <select name="module" class="form-select" required>
                            <option value="sales">Sales</option>
                            <option value="development">Development</option>
                            <option value="budget">Budget</option>
                            <option value="master_plan">Master plan</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Action</label>
                        <input name="action" class="form-control" required placeholder="e.g. cancel_booking">
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Subject type</label>
                        <input name="subject_type" class="form-control" required value="App\Models\PlotBooking">
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Subject ID</label>
                        <input type="number" min="1" name="subject_id" class="form-control" required>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Notes</label>
                        <textarea name="request_notes" class="form-control" rows="2"></textarea>
                    </div>
                    <button class="btn btn-pink btn-sm">Submit</button>
                </form>
            </div>
        </div>
        <div class="card card-theme mt-3">
            <div class="card-body small">
                <strong>Your society role:</strong> {{ auth()->user()->effectiveSocietyRole() }}
                <div class="text-muted mt-1">Can manage: {{ auth()->user()->canManageSociety() ? 'yes' : 'no' }} · Can approve: {{ auth()->user()->canApproveSociety() ? 'yes' : 'no' }}</div>
            </div>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="card card-theme">
            <div class="card-body table-responsive">
                <table class="table table-theme align-middle mb-0">
                    <thead>
                        <tr>
                            <th>When</th>
                            <th>Module / action</th>
                            <th>Subject</th>
                            <th>By</th>
                            <th>Status</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($requests as $req)
                            <tr>
                                <td class="small text-nowrap">{{ $req->created_at?->format('d M Y H:i') }}</td>
                                <td>{{ $req->module }} / {{ $req->action }}</td>
                                <td class="small">{{ class_basename($req->subject_type) }} #{{ $req->subject_id }}</td>
                                <td class="small">{{ $req->requester?->name }}</td>
                                <td><span class="badge text-bg-secondary">{{ $req->status }}</span></td>
                                <td class="text-end">
                                    @if($req->isPending() && auth()->user()->canApproveSociety())
                                        <form method="post" action="{{ route('society.approvals.review', [$project, $req]) }}" class="d-inline-flex gap-1">
                                            @csrf
                                            <input type="hidden" name="decision" value="approved">
                                            <button class="btn btn-sm btn-outline-success">Approve</button>
                                        </form>
                                        <form method="post" action="{{ route('society.approvals.review', [$project, $req]) }}" class="d-inline-flex gap-1">
                                            @csrf
                                            <input type="hidden" name="decision" value="rejected">
                                            <button class="btn btn-sm btn-outline-danger">Reject</button>
                                        </form>
                                    @elseif($req->reviewer)
                                        <span class="small text-muted">by {{ $req->reviewer->name }}</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-muted text-center">No approval requests.</td></tr>
                        @endforelse
                    </tbody>
                </table>
                <div class="mt-3">{{ $requests->links() }}</div>
            </div>
        </div>
    </div>
</div>
@endsection
