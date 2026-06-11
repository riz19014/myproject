@extends('layouts.app')

@section('title', 'Files — '.$project->name)

@section('content')
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
    <div>
        <h1 class="mb-1">Files</h1>
        <p class="text-muted small mb-0">Project: <strong>{{ $project->name }}</strong></p>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <a href="{{ route('sale.projects.exemption.edit', $project) }}" class="btn btn-outline-theme">Exemption setup</a>
        <a href="{{ route('sale.files.create', $project) }}" class="btn btn-pink">Add file</a>
        <a href="{{ route('projects.show', $project) }}" class="btn btn-outline-theme">Back to project</a>
        <a href="{{ route('sale.index') }}" class="btn btn-outline-theme">Sale menu</a>
    </div>
</div>

<div class="card card-theme">
    <div class="card-body">
        @if($project->projectFiles->isEmpty())
            <p class="text-muted mb-0">No files for this project. <a href="{{ route('sale.files.create', $project) }}">Add a file</a> with its total land (e.g. 30 kanal).</p>
        @else
            <div class="table-responsive">
                <table class="table table-striped table-theme mb-0 align-middle">
                    <thead>
                        <tr>
                            <th style="width: 56px;">#</th>
                            <th>File</th>
                            <th>Total land</th>
                            <th class="text-end">Sold</th>
                            <th class="text-end">Remaining</th>
                            <th>Dealer</th>
                            <th style="width: 100px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($project->projectFiles as $file)
                            @php
                                $totalMarla = (float) $file->land_area_marla;
                                $soldMarla = $file->soldMarla();
                                $remaining = $file->remainingMarla();
                            @endphp
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td class="fw-semibold">{{ $file->file_number }}</td>
                                <td class="small">
                                    @if($totalMarla > 0)
                                        {{ \App\Support\LandMeasure::formatAkmsLabelFromMarla($totalMarla) }}
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td class="text-end small">{{ $soldMarla > 0 ? \App\Support\LandMeasure::formatAkmsLabelFromMarla($soldMarla) : '—' }}</td>
                                <td class="text-end small fw-semibold">{{ $totalMarla > 0 ? \App\Support\LandMeasure::formatAkmsLabelFromMarla($remaining) : '—' }}</td>
                                <td class="small">{{ $file->dealerParty?->name ?? '—' }}</td>
                                <td>
                                    <a href="{{ route('sale.files.sale.create', $file) }}" class="btn btn-sm btn-pink">Sale</a>
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
