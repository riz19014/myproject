@extends('layouts.app')

@section('title', $project->name)

@section('content')
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
    <h1 class="mb-0">{{ $project->name }}</h1>
    <div class="d-flex flex-wrap gap-2">
        <a href="{{ route('projects.ledger.pdf', $project) }}" class="btn btn-pink">Download ledger PDF</a>
        <a href="{{ route('projects.edit', $project) }}" class="btn btn-outline-theme">Edit</a>
        <a href="{{ route('projects.index') }}" class="btn btn-outline-theme">Back to List</a>
    </div>
</div>

@if($project->land_area !== null || $project->field_type || $project->landType)
<div class="card card-theme mb-4">
    <div class="card-body py-3">
        <h5 class="mb-2">Land</h5>
        <dl class="row mb-0 small">
            @if($project->land_area !== null && $project->land_area !== '')
                <dt class="col-sm-3 text-muted">Area</dt>
                <dd class="col-sm-9 mb-1">{{ rtrim(rtrim(number_format((float) $project->land_area, 4, '.', ''), '0'), '.') ?: '0' }}
                    @if($project->land_area_unit)
                        @php
                            $u = $project->land_area_unit;
                            $uLabel = match ($u) {
                                'acre' => 'acre',
                                'kanal' => 'kanal',
                                'marla' => 'marla',
                                'sqft' => 'sq ft',
                                default => $u,
                            };
                        @endphp
                        {{ $uLabel }}
                    @endif
                </dd>
            @endif
            @if($project->field_type)
                <dt class="col-sm-3 text-muted">Field type</dt>
                <dd class="col-sm-9 mb-1 text-capitalize">{{ $project->field_type }}</dd>
            @endif
            @if($project->landType)
                <dt class="col-sm-3 text-muted">Land type</dt>
                <dd class="col-sm-9 mb-0">{{ $project->landType->name }}</dd>
            @endif
        </dl>
    </div>
</div>
@endif

@if($project->description || $project->notes)
<div class="card card-theme mb-4">
    <div class="card-body">
        @if($project->description)<p class="mb-1">{{ $project->description }}</p>@endif
        @if($project->notes)<p class="mb-0 text-muted small">{{ $project->notes }}</p>@endif
    </div>
</div>
@endif

{{-- Add file (e.g. 50 files from DHA) --}}
<div class="card card-theme mb-4">
    <div class="card-body">
        <h5 class="mb-3">Add File to Project</h5>
        <form action="{{ route('projects.files.store', $project) }}" method="POST" class="row g-2 align-items-end">
            @csrf
            <div class="col-md-4">
                <label for="file_number" class="form-label">File Number</label>
                <input type="text" class="form-control form-control-theme" id="file_number" name="file_number" placeholder="e.g. F-001" required>
            </div>
            <div class="col-md-4">
                <label for="notes" class="form-label">Notes</label>
                <input type="text" class="form-control form-control-theme" name="notes" placeholder="Optional">
            </div>
            <div class="col-md-4">
                <button type="submit" class="btn btn-pink">Add File</button>
            </div>
        </form>
    </div>
</div>

{{-- Files list --}}
<div class="card card-theme mb-4">
    <div class="card-body">
        <h5 class="mb-3">Files ({{ $project->projectFiles->count() }})</h5>
        <div class="table-responsive">
            <table class="table table-striped table-theme">
                <thead>
                    <tr>
                        <th>File #</th>
                        <th>Status</th>
                        <th>Customer</th>
                        <th>Sale Amount</th>
                        <th>Documents</th>
                        <th width="220">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($project->projectFiles as $file)
                        <tr>
                            <td>{{ $file->file_number }}</td>
                            <td>
                                @if($file->status === 'sold')
                                    <span class="badge badge-pink">Sold</span>
                                @else
                                    <span class="badge badge-outline">Available</span>
                                @endif
                            </td>
                            <td>{{ $file->customer?->name ?? '—' }}</td>
                            <td>{{ $file->sale_amount ? number_format($file->sale_amount) : '—' }}</td>
                            <td>{{ $file->documents->count() }}</td>
                            <td>
                                @if($file->status === 'available')
                                    <button type="button" class="btn btn-sm btn-pink" data-bs-toggle="modal" data-bs-target="#sellFileModal{{ $file->id }}">Sell</button>
                                    <div class="modal fade" id="sellFileModal{{ $file->id }}" tabindex="-1">
                                        <div class="modal-dialog">
                                            <div class="modal-content" style="background: var(--card-bg); border: 1px solid var(--border-dark); color: var(--text-dark);">
                                                <div class="modal-header border-secondary">
                                                    <h5 class="modal-title">Sell File {{ $file->file_number }}</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <form action="{{ route('projects.files.sell', [$project, $file]) }}" method="POST">
                                                    @csrf
                                                    @method('PUT')
                                                    <div class="modal-body">
                                                        <div class="mb-3">
                                                            <label class="form-label">Customer <span class="text-danger">*</span></label>
                                                            <select name="customer_id" class="form-select form-select-theme" required>
                                                                <option value="">Select customer</option>
                                                                @foreach(\App\Models\Customer::orderBy('name')->get() as $c)
                                                                    <option value="{{ $c->id }}">{{ $c->name }} @if($c->phone)({{ $c->phone }})@endif</option>
                                                                @endforeach
                                                            </select>
                                                            <small class="text-muted"><a href="{{ route('customers.create') }}" target="_blank">Add new customer</a></small>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label">Sale Amount</label>
                                                            <input type="number" step="0.01" name="sale_amount" class="form-control form-control-theme" placeholder="0">
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label">Sale Date</label>
                                                            <input type="date" name="sale_date" class="form-control form-control-theme" value="{{ date('Y-m-d') }}">
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer border-secondary">
                                                        <button type="button" class="btn btn-outline-theme" data-bs-dismiss="modal">Cancel</button>
                                                        <button type="submit" class="btn btn-pink">Mark as Sold</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                @endif
                                <button type="button" class="btn btn-sm btn-outline-theme" data-bs-toggle="modal" data-bs-target="#uploadDocModal{{ $file->id }}">Upload Doc</button>
                                <div class="modal fade" id="uploadDocModal{{ $file->id }}" tabindex="-1">
                                    <div class="modal-dialog">
                                        <div class="modal-content" style="background: var(--card-bg); border: 1px solid var(--border-dark); color: var(--text-dark);">
                                            <div class="modal-header border-secondary">
                                                <h5 class="modal-title">Upload documents – {{ $file->file_number }}</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <form action="{{ route('projects.files.documents.store', [$project, $file]) }}" method="POST" enctype="multipart/form-data">
                                                @csrf
                                                <div class="modal-body">
                                                    <input type="file" name="documents[]" class="form-control form-control-theme mb-2" multiple accept=".pdf,.jpg,.jpeg,.png,.doc,.docx">
                                                    <p class="small text-muted mb-0">You can select multiple files. No strict limit.</p>
                                                </div>
                                                <div class="modal-footer border-secondary">
                                                    <button type="button" class="btn btn-outline-theme" data-bs-dismiss="modal">Cancel</button>
                                                    <button type="submit" class="btn btn-pink">Upload</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                                @foreach($file->documents as $doc)
                                    <span class="d-inline-block me-1 mt-1">
                                        <a href="{{ asset('storage/' . $doc->file_path) }}" target="_blank" class="btn btn-sm btn-outline-theme">{{ $doc->name ?? 'Doc' }}</a>
                                        <form action="{{ route('projects.files.documents.destroy', [$project, $file, $doc->id]) }}" method="POST" class="d-inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="button" class="btn btn-sm btn-danger-theme btn-delete-confirm" data-title="Remove document?">×</button>
                                        </form>
                                    </span>
                                @endforeach
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center">No files yet. Add files using the form above (e.g. when DHA provides files).</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- DayBook ledger for this project — grouped by party --}}
<div class="card card-theme">
    <div class="card-body">
        <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
            <div>
                <h5 class="mb-1">DayBook — {{ $project->name }}</h5>
                <p class="text-muted small mb-0">All lines tied to this project (project link, or party with this project selected in DayBook). Grouped by party; “General” is project-only entries.</p>
            </div>
            <a href="{{ route('projects.ledger.pdf', $project) }}" class="btn btn-sm btn-outline-theme">Ledger PDF</a>
        </div>

        @if($entries->isEmpty())
            <p class="text-muted mb-0">No daybook entries yet. Add them from <a href="{{ route('daybook.index') }}">Daybook</a> and choose this project (and optionally a party).</p>
        @else
            <div class="row g-2 mb-3">
                <div class="col-sm-4">
                    <div class="border rounded p-2 small bg-light">
                        <span class="text-muted d-block">Total payment in</span>
                        <span class="text-success fw-semibold">Rs {{ number_format($ledgerTotalIn, 2) }}</span>
                    </div>
                </div>
                <div class="col-sm-4">
                    <div class="border rounded p-2 small bg-light">
                        <span class="text-muted d-block">Total payment out</span>
                        <span class="text-danger fw-semibold">Rs {{ number_format($ledgerTotalOut, 2) }}</span>
                    </div>
                </div>
                <div class="col-sm-4">
                    <div class="border rounded p-2 small bg-light">
                        <span class="text-muted d-block">Net (in − out)</span>
                        <span class="fw-semibold">Rs {{ number_format($ledgerNetFlow, 2) }}</span>
                    </div>
                </div>
            </div>

            @foreach($ledgerSections as $section)
                <div class="mb-4">
                    <h6 class="text-uppercase small text-muted mb-2" style="letter-spacing: 0.06em;">{{ $section['heading'] }}</h6>
                    @if(!empty($section['subtitle']))
                        <p class="small text-muted mb-2">{{ $section['subtitle'] }}</p>
                    @endif
                    <div class="table-responsive">
                        <table class="table table-sm table-striped table-theme mb-0">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Description</th>
                                    <th>Payment</th>
                                    <th class="text-end">Amount</th>
                                    <th class="text-end">Section balance</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($section['lines'] as $row)
                                    @php $e = $row['entry']; @endphp
                                    <tr>
                                        <td>{{ $e->entry_date->format('d M Y') }}</td>
                                        <td>{{ $e->description ?: '—' }}</td>
                                        <td>
                                            @if($e->type === 'cash_in')
                                                <span class="text-success">Payment in</span>
                                            @else
                                                <span class="text-danger">Payment out</span>
                                            @endif
                                        </td>
                                        <td class="text-end font-monospace">
                                            @if($e->type === 'cash_in')
                                                +{{ number_format($e->amount, 2) }}
                                            @else
                                                −{{ number_format($e->amount, 2) }}
                                            @endif
                                        </td>
                                        <td class="text-end font-monospace">{{ number_format($row['balance'], 2) }}</td>
                                    </tr>
                                @endforeach
                                <tr class="table-light fw-semibold">
                                    <td colspan="4" class="text-end">Net — {{ $section['heading'] }}</td>
                                    <td class="text-end font-monospace">{{ number_format($section['net'], 2) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            @endforeach
        @endif
    </div>
</div>
@endsection
