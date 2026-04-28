@extends('layouts.app')

@section('title', $land->name)

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1>{{ $land->name }}</h1>
    <div class="d-flex gap-2">
        <a href="{{ route('lands.edit', $land) }}" class="btn btn-outline-theme">Edit</a>
        <a href="{{ route('lands.index') }}" class="btn btn-outline-theme">Back to List</a>
    </div>
</div>

<div class="card card-theme mb-4">
    <div class="card-body">
        <p class="mb-1"><strong>Area:</strong> {{ $land->total_area_kanal ? $land->total_area_kanal . ' Kanal' : '—' }} &nbsp;|&nbsp; <strong>Location:</strong> {{ $land->location ?? '—' }} &nbsp;|&nbsp; <strong>Purchase date:</strong> {{ $land->purchase_date?->format('d M Y') ?? '—' }}</p>
        @if($land->notes)<p class="mb-0 text-muted small">{{ $land->notes }}</p>@endif
    </div>
</div>

{{-- Add plot --}}
<div class="card card-theme mb-4">
    <div class="card-body">
        <h5 class="mb-3">Add Plot</h5>
        <form action="{{ route('lands.plots.store', $land) }}" method="POST" class="row g-2 align-items-end">
            @csrf
            <div class="col-md-3">
                <label for="plot_number" class="form-label">Plot Number <span class="text-danger">*</span></label>
                <input type="text" class="form-control form-control-theme" id="plot_number" name="plot_number" placeholder="e.g. P-1" required>
            </div>
            <div class="col-md-3">
                <label for="size" class="form-label">Size</label>
                <input type="text" class="form-control form-control-theme" name="size" placeholder="e.g. 10 Marla">
            </div>
            <div class="col-md-3">
                <label for="notes" class="form-label">Notes</label>
                <input type="text" class="form-control form-control-theme" name="notes">
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn-pink">Add Plot</button>
            </div>
        </form>
    </div>
</div>

{{-- Plots list --}}
<div class="card card-theme mb-4">
    <div class="card-body">
        <h5 class="mb-3">Plots ({{ $land->plots->count() }})</h5>
        <div class="table-responsive">
            <table class="table table-striped table-theme">
                <thead>
                    <tr>
                        <th>Plot #</th>
                        <th>Size</th>
                        <th>Status</th>
                        <th>Customer</th>
                        <th>Sale Amount</th>
                        <th>Docs</th>
                        <th width="220">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($land->plots as $plot)
                        <tr>
                            <td>{{ $plot->plot_number }}</td>
                            <td>{{ $plot->size ?? '—' }}</td>
                            <td>
                                @if($plot->status === 'sold')
                                    <span class="badge badge-pink">Sold</span>
                                @else
                                    <span class="badge badge-outline">Available</span>
                                @endif
                            </td>
                            <td>{{ $plot->customer?->name ?? '—' }}</td>
                            <td>{{ $plot->sale_amount ? number_format($plot->sale_amount) : '—' }}</td>
                            <td>{{ $plot->documents->count() }}</td>
                            <td>
                                @if($plot->status === 'available')
                                    <button type="button" class="btn btn-sm btn-pink" data-bs-toggle="modal" data-bs-target="#sellPlotModal{{ $plot->id }}">Sell</button>
                                    <div class="modal fade" id="sellPlotModal{{ $plot->id }}" tabindex="-1">
                                        <div class="modal-dialog">
                                            <div class="modal-content" style="background: var(--card-bg); border: 1px solid var(--border-dark); color: var(--text-dark);">
                                                <div class="modal-header border-secondary">
                                                    <h5 class="modal-title">Sell Plot {{ $plot->plot_number }}</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <form action="{{ route('lands.plots.sell', [$land, $plot]) }}" method="POST">
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
                                <button type="button" class="btn btn-sm btn-outline-theme" data-bs-toggle="modal" data-bs-target="#uploadPlotDocModal{{ $plot->id }}">Upload Doc</button>
                                <div class="modal fade" id="uploadPlotDocModal{{ $plot->id }}" tabindex="-1">
                                    <div class="modal-dialog">
                                        <div class="modal-content" style="background: var(--card-bg); border: 1px solid var(--border-dark); color: var(--text-dark);">
                                            <div class="modal-header border-secondary">
                                                <h5 class="modal-title">Upload documents – {{ $plot->plot_number }}</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <form action="{{ route('lands.plots.documents.store', [$land, $plot]) }}" method="POST" enctype="multipart/form-data">
                                                @csrf
                                                <div class="modal-body">
                                                    <input type="file" name="documents[]" class="form-control form-control-theme mb-2" multiple accept=".pdf,.jpg,.jpeg,.png,.doc,.docx">
                                                </div>
                                                <div class="modal-footer border-secondary">
                                                    <button type="button" class="btn btn-outline-theme" data-bs-dismiss="modal">Cancel</button>
                                                    <button type="submit" class="btn btn-pink">Upload</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                                @foreach($plot->documents as $doc)
                                    <span class="d-inline-block me-1 mt-1">
                                        <a href="{{ asset('storage/' . $doc->file_path) }}" target="_blank" class="btn btn-sm btn-outline-theme">{{ $doc->name ?? 'Doc' }}</a>
                                        <form action="{{ route('lands.plots.documents.destroy', [$land, $plot, $doc->id]) }}" method="POST" class="d-inline">
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
                            <td colspan="7" class="text-center">No plots yet. Add plots using the form above.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- Payments for this land (from DayBook) --}}
<div class="card card-theme">
    <div class="card-body">
        <h5 class="mb-3">Payments for this land (from DayBook)</h5>
        <p class="text-muted small">Payments entered in DayBook with link “Land → {{ $land->name }}” appear here.</p>
        <table class="table table-striped table-theme">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Type</th>
                    <th>Amount</th>
                    <th>Description</th>
                </tr>
            </thead>
            <tbody>
                @forelse($paymentsLand as $e)
                    <tr>
                        <td>{{ $e->entry_date->format('d M Y') }}</td>
                        <td>{{ $e->type === 'cash_in' ? 'Payment in' : 'Payment out' }}</td>
                        <td>{{ number_format($e->amount) }}</td>
                        <td>{{ $e->description ?? '—' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="text-center">No payments linked yet. <a href="{{ route('daybook.create') }}">Add in DayBook</a> and link to this land.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
