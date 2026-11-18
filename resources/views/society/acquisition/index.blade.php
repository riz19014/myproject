@extends('layouts.app')

@section('title', 'Land acquisition — '.$project->name)

@section('content')
@include('society.partials.nav')

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
    <div>
        <h1 class="h3 mb-1">Land acquisition</h1>
        <p class="text-muted mb-0"><x-project-name :project="$project" /> — purchased land, ownership files, and costs.</p>
    </div>
    <a href="{{ route('purchase.files.create', ['project' => $project->id]) }}" class="btn btn-pink">Add purchase file</a>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card card-theme h-100">
            <div class="card-body">
                <div class="text-muted small">Total purchased</div>
                <div class="fs-5 fw-semibold">{{ $purchasedLabel }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card card-theme h-100">
            <div class="card-body">
                <div class="text-muted small">Purchase cost (line totals)</div>
                <div class="fs-5 fw-semibold">Rs {{ number_format($purchaseCost, 2) }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card card-theme h-100">
            <div class="card-body">
                <div class="text-muted small">Purchase files</div>
                <div class="fs-5 fw-semibold">{{ $project->purchaseFiles->count() }}</div>
            </div>
        </div>
    </div>
</div>

<div class="card card-theme">
    <div class="card-body">
        <p class="small text-muted">Registry / legal expenses: post as daybook cash-out against this project (Government &amp; Legal categories). Ownership is tracked per seller on each file.</p>
        <div class="table-responsive">
            <table class="table table-theme table-striped align-middle mb-0">
                <thead>
                    <tr>
                        <th>File</th>
                        <th>Date</th>
                        <th>Sellers</th>
                        <th>Area</th>
                        <th>Cost</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($project->purchaseFiles as $file)
                        @php
                            $sellers = $file->purchaseItems->map(fn ($i) => $i->party?->name)->filter()->unique()->values();
                            $marla = (float) $file->purchaseItems->sum(fn ($i) => (float) $i->effectiveLandAreaMarla());
                            $cost = (float) $file->purchaseItems->sum('line_total_rs');
                        @endphp
                        <tr>
                            <td class="fw-semibold">{{ $file->file_name }}</td>
                            <td class="small text-nowrap">{{ $file->file_date?->format('d M Y') ?? '—' }}</td>
                            <td class="small">{{ $sellers->isNotEmpty() ? $sellers->join(', ') : '—' }}</td>
                            <td class="small font-monospace">{{ \App\Support\LandMeasure::formatAkmsLabelFromMarla($marla) }}</td>
                            <td class="small">Rs {{ number_format($cost, 2) }}</td>
                            <td class="text-end">
                                <a href="{{ route('purchase.files.sellers', $file) }}" class="btn btn-sm btn-outline-theme">Sellers</a>
                                <a href="{{ route('purchase.files.documents', $file) }}" class="btn btn-sm btn-outline-theme">Docs</a>
                                <a href="{{ route('purchase.files.show', $file) }}" class="btn btn-sm btn-outline-theme">Open</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">
                                No purchase files yet.
                                <a href="{{ route('purchase.files.create', ['project' => $project->id]) }}">Add the land purchase</a>.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
