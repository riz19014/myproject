@extends('layouts.app')

@section('title', 'Sale')

@section('content')
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
    <div>
        <h1 class="mb-1 d-flex align-items-center gap-2">
            <i class="bi bi-graph-up-arrow" aria-hidden="true"></i>
            <span>Sale</span>
        </h1>
        <p class="text-muted small mb-0">Select a project, then its files, then record a direct plot sale or percentage deal sale.</p>
    </div>
    <a href="{{ route('projects.create', ['context' => 'sale']) }}" class="btn btn-outline-theme">Add project</a>
</div>

<div class="card card-theme">
    <div class="card-body">
        <h2 class="h5 mb-3">Projects</h2>
        @if($projects->isEmpty())
            <p class="text-muted mb-0">No projects yet. <a href="{{ route('projects.create', ['context' => 'sale']) }}">Add a project</a> first.</p>
        @else
            <div class="table-responsive">
                <table class="table table-striped table-theme mb-0 align-middle">
                    <thead>
                        <tr>
                            <th style="width: 56px;">#</th>
                            <th>Project</th>
                            <th>Land type</th>
                            <th class="text-center">Files</th>
                            <th style="width: 140px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($projects as $project)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td class="fw-semibold">{{ $project->name }}</td>
                                <td class="small text-muted">{{ $project->landType?->name ?? '—' }}</td>
                                <td class="text-center">{{ $project->project_files_count }}</td>
                                <td>
                                    <a href="{{ route('sale.files.index', $project) }}" class="btn btn-sm btn-pink">Files</a>
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
