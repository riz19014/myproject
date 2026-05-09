@extends('layouts.app')

@php
    $label = $type === 'sale' ? 'Sale' : 'Purchase';
    $icon = $type === 'sale' ? 'bi-graph-up-arrow' : 'bi-bag-check';
@endphp

@section('title', $label)

@section('content')
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
    <div>
        <h1 class="mb-1 d-flex align-items-center gap-2">
            <i class="bi {{ $icon }}" aria-hidden="true"></i>
            <span>{{ $label }}</span>
        </h1>
        @if($type === 'sale')
            <div class="text-muted small">Sale lines, land cuttings, and net saleable area by project.</div>
        @else
            <div class="text-muted small">Projects where type is <strong class="text-capitalize">{{ $type }}</strong>.</div>
        @endif
    </div>
    <div class="d-flex flex-wrap gap-2">
        @if($type === 'sale')
            <a href="{{ route('sale.records.create') }}" class="btn btn-pink">Add sale</a>
            <a href="{{ route('projects.create', ['context' => 'sale']) }}" class="btn btn-outline-theme">Add sale project</a>
        @else
            <a href="{{ route('projects.create', ['context' => $type]) }}" class="btn btn-pink">Add Project</a>
        @endif
        <a href="{{ route('projects.index') }}" class="btn btn-outline-theme">All projects</a>
    </div>
</div>

@if($type === 'sale')
    <div class="card card-theme mb-4">
        <div class="card-body">
            <h2 class="h5 mb-3">Sales</h2>
            @if($sales->isEmpty())
                <p class="text-muted small mb-0">No sale lines yet. Use <strong>Add sale</strong> to record land area, parties or buyers, and amount against a sale project.</p>
            @else
                <div class="table-responsive">
                    <table class="table table-striped table-theme mb-0 align-middle">
                        <thead>
                            <tr>
                                <th style="width: 72px;">ID</th>
                                <th>Project</th>
                                <th style="min-width: 200px;">Sale land</th>
                                <th class="text-end" style="width: 120px;">Cuttings</th>
                                <th class="text-end" style="width: 120px;">Net saleable</th>
                                <th>Parties / buyers</th>
                                <th class="text-end" style="width: 120px;">Total (Rs)</th>
                                <th style="width: 100px;">Date</th>
                                <th class="text-center" style="width: 56px;" title="Land cutting"><span class="visually-hidden">Cuttings</span></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($sales as $sale)
                                @php
                                    $names = $sale->participants->map(function ($sp) {
                                        return $sp->party?->name ?? $sp->customer?->name ?? '—';
                                    })->filter()->values();
                                    $cutMarla = (float) $sale->landCuttings->sum('land_area_marla');
                                    $netMarla = (float) $sale->land_area_marla - $cutMarla;
                                @endphp
                                <tr>
                                    <td>{{ $sale->id }}</td>
                                    <td class="fw-semibold">{{ $sale->project?->name ?? '—' }}</td>
                                    <td class="small">{{ \App\Support\LandMeasure::formatAkmsLabelFromMarla((float) $sale->land_area_marla) }}</td>
                                    <td class="text-end small">{{ $cutMarla > 0 ? \App\Support\LandMeasure::formatAkmsLabelFromMarla($cutMarla) : '—' }}</td>
                                    <td class="text-end small fw-semibold {{ $netMarla < 0 ? 'text-danger' : '' }}">
                                        @if($netMarla < 0)
                                            −{{ number_format(abs($netMarla), 2) }} marla
                                        @else
                                            {{ \App\Support\LandMeasure::formatAkmsLabelFromMarla($netMarla) }}
                                        @endif
                                    </td>
                                    <td class="small">{{ $names->isEmpty() ? '—' : $names->implode(', ') }}</td>
                                    <td class="text-end fw-semibold">{{ number_format((float) $sale->total_amount, 0) }}</td>
                                    <td class="text-muted small">{{ $sale->created_at?->format('Y-m-d') }}</td>
                                    <td class="text-center">
                                        <a href="{{ route('sale.records.land-cuttings.index', $sale) }}" class="btn btn-sm btn-outline-theme p-2" title="Land cutting" aria-label="Land cutting for sale {{ $sale->id }}">
                                            <i class="bi bi-scissors" aria-hidden="true"></i>
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
@endif

<div class="row g-3 mb-4">
    <div class="col-sm-6 col-lg-3">
        <div class="card card-theme h-100">
            <div class="card-body">
                <div class="text-muted small fw-semibold text-uppercase" style="letter-spacing:.08em;">Projects</div>
                <div class="fs-4 fw-bold">{{ $projects->count() }}</div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="card card-theme h-100">
            <div class="card-body">
                <div class="text-muted small fw-semibold text-uppercase" style="letter-spacing:.08em;">Total amount</div>
                <div class="fs-4 fw-bold">Rs {{ number_format($totalAmount, 0) }}</div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="card card-theme h-100">
            <div class="card-body">
                <div class="text-muted small fw-semibold text-uppercase" style="letter-spacing:.08em;">With land type</div>
                <div class="fs-4 fw-bold">{{ $projects->whereNotNull('land_type_id')->count() }}</div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="card card-theme h-100">
            <div class="card-body">
                <div class="text-muted small fw-semibold text-uppercase" style="letter-spacing:.08em;">No land type</div>
                <div class="fs-4 fw-bold">{{ $projects->whereNull('land_type_id')->count() }}</div>
            </div>
        </div>
    </div>
</div>

@forelse($byLandType as $landTypeId => $rows)
    @php
        $landTypeName = $landTypeId && $rows->first() && $rows->first()->landType
            ? $rows->first()->landType->name
            : ($landTypeId ? ('Land type #' . $landTypeId) : 'Uncategorized');
        $sectionTotal = (float) $rows->sum('total_amount');
    @endphp

    <div class="card card-theme mb-4">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                <div>
                    <h5 class="mb-0">{{ $landTypeName }}</h5>
                    <div class="text-muted small">{{ $rows->count() }} project(s) · Rs {{ number_format($sectionTotal, 0) }}</div>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-striped table-theme mb-0 align-middle">
                    <thead>
                        <tr>
                            <th style="width: 72px;">ID</th>
                            <th>Name</th>
                            <th style="width: 160px;">Area</th>
                            <th class="text-end" style="width: 180px;">Total (Rs)</th>
                            <th class="text-center" style="width: 90px;">Files</th>
                            <th style="width: 220px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($rows as $project)
                            <tr>
                                <td>{{ $project->id }}</td>
                                <td class="fw-semibold">{{ $project->name }}</td>
                                <td>
                                    @if($project->land_area !== null && $project->land_area !== '')
                                        {{ rtrim(rtrim(number_format((float) $project->land_area, 4, '.', ''), '0'), '.') ?: '0' }}
                                        {{ $project->land_area_unit ?? '' }}
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td class="text-end fw-semibold">
                                    @if($project->total_amount !== null && $project->total_amount !== '')
                                        {{ number_format((float) $project->total_amount, 0) }}
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td class="text-center">{{ $project->project_files_count }}</td>
                                <td>
                                    <a href="{{ route('projects.show', $project) }}" class="btn btn-sm btn-outline-theme">View</a>
                                    <a href="{{ route('projects.edit', $project) }}" class="btn btn-sm btn-outline-theme">Edit</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@empty
    <div class="card card-theme">
        <div class="card-body text-center py-5">
            <div class="text-muted">No {{ strtolower($label) }} projects yet.</div>
            <div class="mt-3">
                @if($type === 'sale')
                    <a href="{{ route('projects.create', ['context' => 'sale']) }}" class="btn btn-pink">Add sale project</a>
                @else
                    <a href="{{ route('projects.create', ['context' => $type]) }}" class="btn btn-pink">Create a project</a>
                @endif
            </div>
        </div>
    </div>
@endforelse
@endsection

