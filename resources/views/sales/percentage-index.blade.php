@extends('layouts.app')

@section('title', 'Sale percentage')

@section('content')
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
    <div>
        <h1 class="mb-1 d-flex align-items-center gap-2">
            <i class="bi bi-percent" aria-hidden="true"></i>
            <span>Sale percentage</span>
        </h1>
        <p class="text-muted small mb-0">Select a project file to open the percentage sale estimator and plot file calculator.</p>
    </div>
    <a href="{{ route('sale.index') }}" class="btn btn-outline-theme btn-sm">Sale menu</a>
</div>

<div class="card card-theme">
    <div class="card-body">
        @if($files->isEmpty())
            <p class="text-muted mb-0">
                No sale files yet.
                @if($projects->isNotEmpty())
                    <a href="{{ route('sale.files.index', $projects->first()) }}">Add a file</a> under a project first.
                @else
                    <a href="{{ route('projects.create', ['context' => 'sale']) }}">Add a project</a> and file first.
                @endif
            </p>
        @else
            <div class="table-responsive">
                <table class="table table-sm table-striped table-theme mb-0 align-middle">
                    <thead>
                        <tr>
                            <th style="width: 48px;">#</th>
                            <th>Project</th>
                            <th>File</th>
                            <th>Land</th>
                            <th>Dealer</th>
                            <th style="width: 120px;"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($files as $file)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td class="small">@if($file->project)<x-project-name :project="$file->project" />@else—@endif</td>
                                <td class="fw-semibold">{{ $file->file_number }}</td>
                                <td class="small">
                                    @if((float) $file->land_area_marla > 0)
                                        {{ \App\Support\LandMeasure::formatAkmsLabelFromMarla((float) $file->land_area_marla) }}
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td class="small text-muted">{{ $file->dealerParty?->name ?? '—' }}</td>
                                <td>
                                    <a href="{{ route('sale.files.sale.create', ['projectFile' => $file, 'type' => 'percentage']) }}" class="btn btn-sm btn-pink">Open</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
@endsection
