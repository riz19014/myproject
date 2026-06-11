@extends('layouts.app')

@section('title', 'Projects')

@section('content')
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
    <h1 class="mb-0">Projects</h1>
    <a href="{{ route('projects.create') }}" class="btn btn-pink">Add Project</a>
</div>

<div class="card card-theme mb-4">
    <div class="card-body">
        <form method="get" action="{{ route('projects.index') }}" id="projects-search-form" class="row g-2 align-items-end">
            <div class="col-md-9 col-lg-10">
                <label for="q" class="form-label">Search projects</label>
                <input type="search" name="q" id="q" class="form-control form-control-theme" value="{{ $search ?? '' }}" placeholder="Project name or land type…" autocomplete="off">
            </div>
            <div class="col-md-3 col-lg-2 d-flex flex-wrap gap-2">
                <button type="submit" class="btn btn-pink flex-grow-1" id="projects-search-btn">
                    <span class="spinner-border spinner-border-sm me-1 d-none" id="projects-search-spinner" role="status" aria-hidden="true"></span>
                    Search
                </button>
                @if(($search ?? '') !== '')
                    <a href="{{ route('projects.index') }}" class="btn btn-outline-theme">Clear</a>
                @endif
            </div>
        </form>
    </div>
</div>

<div class="card card-theme">
    <div class="card-body">
        @if($projects->isEmpty())
            @if(($search ?? '') !== '')
                <p class="text-muted mb-0">No projects match your search. <a href="{{ route('projects.index') }}">Clear search</a> or <a href="{{ route('projects.create') }}">create a project</a>.</p>
            @else
                <p class="text-muted mb-0">No projects yet. <a href="{{ route('projects.create') }}">Create one</a>.</p>
            @endif
        @else
            <div class="table-responsive">
                <table class="table table-striped table-theme mb-0 align-middle">
                    <thead>
                        <tr>
                            <th style="width: 56px;">#</th>
                            <th>Name</th>
                            <th>Land type</th>
                            <th class="text-center" style="width: 90px;">Files</th>
                            <th style="width: 200px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($projects as $project)
                            <tr>
                                <td>{{ $projects->firstItem() + $loop->index }}</td>
                                <td class="fw-semibold">{{ $project->name }}</td>
                                <td class="small text-muted">{{ $project->landType?->name ?? '—' }}</td>
                                <td class="text-center">
                                    <a href="{{ route('purchase.files.index', ['project' => $project->id]) }}" class="badge badge-pink rounded-pill text-decoration-none fw-semibold px-3 py-2" title="Purchase files">
                                        {{ $project->purchase_files_count }}
                                    </a>
                                </td>
                                <td class="text-nowrap">
                                    <a href="{{ route('projects.show', $project) }}" class="btn btn-sm btn-outline-theme">View</a>
                                    <a href="{{ route('projects.edit', $project) }}" class="btn btn-sm btn-outline-theme">Edit</a>
                                    <form action="{{ route('projects.destroy', $project) }}" method="POST" class="d-inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="button" class="btn btn-sm btn-danger-theme btn-delete-confirm" data-title="Delete Project?" data-text="This will delete the project and its purchase files and related data.">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="mt-3">{{ $projects->links() }}</div>
        @endif
    </div>
</div>
@endsection

@push('scripts')
<script>
(function() {
    var form = document.getElementById('projects-search-form');
    var btn = document.getElementById('projects-search-btn');
    var spinner = document.getElementById('projects-search-spinner');
    if (!form || !btn || !spinner) return;
    form.addEventListener('submit', function() {
        btn.disabled = true;
        spinner.classList.remove('d-none');
    });
})();
</script>
@endpush
