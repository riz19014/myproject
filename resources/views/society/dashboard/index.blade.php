@extends('layouts.app')

@section('title', 'Society dashboard — '.$project->name)

@section('content')
@include('society.partials.nav')

<div class="mb-4">
    <h1 class="h3 mb-1">Society dashboard</h1>
    <p class="text-muted mb-0"><x-project-name :project="$project" /> — land, inventory, cash, and development snapshot.</p>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-3"><div class="card card-theme h-100"><div class="card-body">
        <div class="text-muted small">Purchased land</div>
        <div class="fw-semibold">{{ $purchasedLabel }}</div>
    </div></div></div>
    <div class="col-md-3"><div class="card card-theme h-100"><div class="card-body">
        <div class="text-muted small">Allocated</div>
        <div class="fw-semibold">{{ $allocatedLabel }}</div>
        @if(abs($landVariance) >= 0.01)
            <div class="small text-danger">Variance {{ \App\Support\LandMeasure::formatAkmsLabelFromMarla(abs($landVariance)) }}</div>
        @else
            <div class="small text-success">Reconciled</div>
        @endif
    </div></div></div>
    <div class="col-md-3"><div class="card card-theme h-100"><div class="card-body">
        <div class="text-muted small">Cash in / out</div>
        <div class="fw-semibold">Rs {{ number_format($cashIn, 0) }} / {{ number_format($cashOut, 0) }}</div>
        <div class="small text-muted">Net {{ number_format($netCash, 0) }}</div>
    </div></div></div>
    <div class="col-md-3"><div class="card card-theme h-100"><div class="card-body">
        <div class="text-muted small">Sales outstanding</div>
        <div class="fw-semibold">Rs {{ number_format($outstanding, 0) }}</div>
        <div class="small text-muted">Collected {{ number_format($collected, 0) }}</div>
    </div></div></div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-3"><div class="card card-theme"><div class="card-body py-3">
        <div class="text-muted small">Plots available</div>
        <div class="fs-4 fw-semibold">{{ $plotStats['available'] ?? 0 }}</div>
    </div></div></div>
    <div class="col-md-3"><div class="card card-theme"><div class="card-body py-3">
        <div class="text-muted small">Booked</div>
        <div class="fs-4 fw-semibold">{{ $plotStats['booked'] ?? 0 }}</div>
    </div></div></div>
    <div class="col-md-3"><div class="card card-theme"><div class="card-body py-3">
        <div class="text-muted small">Sold</div>
        <div class="fs-4 fw-semibold">{{ $plotStats['sold'] ?? 0 }}</div>
    </div></div></div>
    <div class="col-md-3"><div class="card card-theme"><div class="card-body py-3">
        <div class="text-muted small">Pending approvals</div>
        <div class="fs-4 fw-semibold">{{ $pendingApprovals }}</div>
    </div></div></div>
</div>

<div class="row g-4">
    <div class="col-lg-6">
        <div class="card card-theme h-100">
            <div class="card-body">
                <h5 class="mb-3">Land mix</h5>
                @forelse($allocations as $a)
                    <div class="d-flex justify-content-between small border-bottom py-2">
                        <span>{{ $a->categoryLabel() }}</span>
                        <span class="font-monospace">{{ \App\Support\LandMeasure::formatAkmsLabelFromMarla((float)$a->land_area_marla) }}</span>
                    </div>
                @empty
                    <p class="text-muted mb-0">No master plan yet. <a href="{{ route('society.master-plan', $project) }}">Allocate land</a>.</p>
                @endforelse
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card card-theme h-100">
            <div class="card-body">
                <h5 class="mb-3">Development packages</h5>
                @forelse($packages as $package)
                    <div class="mb-3">
                        <div class="d-flex justify-content-between small mb-1">
                            <span>{{ $package->name }}</span>
                            <span>{{ number_format((float)$package->progress_percent, 0) }}%</span>
                        </div>
                        <div class="progress" style="height:6px"><div class="progress-bar" style="width:{{ min(100,(float)$package->progress_percent) }}%"></div></div>
                    </div>
                @empty
                    <p class="text-muted mb-0">No packages. <a href="{{ route('society.development', $project) }}">Add development work</a>.</p>
                @endforelse
                <hr>
                <div class="small">
                    Purchase cost Rs {{ number_format($purchaseCost, 0) }} ·
                    Sale value Rs {{ number_format($saleValue, 0) }} ·
                    Est. result (collected − out − purchase) Rs {{ number_format($profitEstimate, 0) }}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
