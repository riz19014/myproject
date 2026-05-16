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

@if($project->landType)
<div class="card card-theme mb-4">
    <div class="card-body py-3">
        <h5 class="mb-2">Land</h5>
        <dl class="row mb-0 small">
            <dt class="col-sm-3 text-muted">Land type</dt>
            <dd class="col-sm-9 mb-0">{{ $project->landType->name }}</dd>
        </dl>
    </div>
</div>
@endif


{{-- Purchase files for this project --}}
<div class="card card-theme mb-4">
    <div class="card-body">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
            <h5 class="mb-0">Files ({{ $project->purchaseFiles->count() }})</h5>
            <a href="{{ route('purchase.files.create', ['project' => $project->id]) }}" class="btn btn-sm btn-pink">Add file</a>
        </div>
        <div class="table-responsive">
            <table class="table table-striped table-theme mb-0 align-middle">
                <thead>
                    <tr>
                        <th>File name</th>
                        <th style="width: 100px;">Date</th>
                        <th>Sellers</th>
                        <th>Total land area</th>
                        <th class="text-center" style="width: 56px;">View</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($project->purchaseFiles as $file)
                        @php
                            $sellerNames = $file->purchaseItems
                                ->map(fn ($item) => $item->party?->name)
                                ->filter()
                                ->unique()
                                ->values();
                            $fileTotalMarla = (float) $file->purchaseItems->sum(fn ($item) => (float) $item->land_area_marla);
                        @endphp
                        <tr>
                            <td class="fw-semibold">{{ $file->file_name }}</td>
                            <td class="small text-nowrap">{{ $file->file_date?->format('d M Y') ?? '—' }}</td>
                            <td class="small">
                                @if($sellerNames->isNotEmpty())
                                    {{ $sellerNames->join(', ') }}
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td class="small font-monospace">
                                @if($fileTotalMarla > 0)
                                    {{ \App\Support\LandMeasure::formatAkmsLabelFromMarla($fileTotalMarla) }}
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td class="text-center">
                                <a href="{{ route('purchase.files.sellers', $file) }}" class="btn btn-sm btn-outline-theme" title="View sellers">
                                    <i class="bi bi-eye" aria-hidden="true"></i>
                                    <span class="visually-hidden">View</span>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted">No purchase files for this project yet. <a href="{{ route('purchase.files.create', ['project' => $project->id]) }}">Add one</a>.</td>
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
                <p class="text-muted small mb-0">Grouped by party. Land total is from purchase file sellers; payments from DayBook. Balance payable = land total minus total paid (amounts shown without minus signs).</p>
            </div>
            <a href="{{ route('projects.ledger.pdf', $project) }}" class="btn btn-sm btn-outline-theme">Ledger PDF</a>
        </div>

        @include('projects.partials.daybook-ledger-section')
    </div>
</div>
@endsection
