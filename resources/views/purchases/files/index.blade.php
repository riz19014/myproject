@extends('layouts.app')

@section('title', 'Purchase files')

@section('content')
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
    <div>
        <h1 class="mb-1">Purchase files</h1>
        <p class="text-muted small mb-0">Named files per project. Add <strong>sellers</strong> (party, land, amount) against each file.</p>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <a href="{{ route('purchase.files.create', $projectId ? ['project' => $projectId] : []) }}" class="btn btn-pink">Add purchase file</a>
        <a href="{{ route('purchase.index') }}" class="btn btn-outline-theme">Back to Purchase</a>
    </div>
</div>

<div class="card card-theme mb-4">
    <div class="card-body">
        <form method="get" action="{{ route('purchase.files.index') }}" id="purchase-files-filter-form" class="row g-2 align-items-end">
            <div class="col-md-6 col-lg-4">
                <label for="project" class="form-label">Filter by project</label>
                <select name="project" id="project" class="form-select form-select-theme">
                    <option value="">All projects</option>
                    @foreach($projects as $p)
                        <option value="{{ $p->id }}" @selected((string) $projectId === (string) $p->id)>{{ $p->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-6 col-lg-5">
                <label for="q" class="form-label">Search</label>
                <input type="search" name="q" id="q" class="form-control form-control-theme" value="{{ $search ?? '' }}" placeholder="File name, project, seller or dealer party…" autocomplete="off">
            </div>
            <div class="col-md-12 col-lg-3 d-flex flex-wrap gap-2">
                <button type="submit" class="btn btn-pink flex-grow-1" id="purchase-files-search-btn">
                    <span class="spinner-border spinner-border-sm me-1 d-none" id="purchase-files-search-spinner" role="status" aria-hidden="true"></span>
                    Search
                </button>
                @if(($search ?? '') !== '' || $projectId)
                    <a href="{{ route('purchase.files.index') }}" class="btn btn-outline-theme">Clear</a>
                @endif
            </div>
        </form>
    </div>
</div>

<div class="card card-theme">
    <div class="card-body">
        @if($files->isEmpty())
            @if(($search ?? '') !== '' || $projectId)
                <p class="text-muted mb-0">No purchase files match your filters. <a href="{{ route('purchase.files.index') }}">Clear filters</a> or <a href="{{ route('purchase.files.create') }}">add a file</a>.</p>
            @else
                <p class="text-muted mb-0">No purchase files yet. <a href="{{ route('purchase.files.create') }}">Create one</a>, then add sellers.</p>
            @endif
        @else
            <div class="table-responsive">
                <table class="table table-striped table-theme mb-0 align-middle">
                    <thead>
                        <tr>
                            <th style="width: 72px;">ID</th>
                            <th>File name</th>
                            <th style="width: 110px;">File date</th>
                            <th>Project</th>
                            <th class="text-center" style="width: 90px;">Sellers</th>
                            <th class="text-center" style="width: 110px;">Documents</th>
                            <th style="width: 120px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($files as $file)
                            <tr>
                                <td>{{ $file->id }}</td>
                                <td class="fw-semibold">{{ $file->file_name }}</td>
                                <td class="small text-nowrap">{{ $file->file_date?->format('d M Y') ?? '—' }}</td>
                                <td class="small">{{ $file->project?->name ?? '—' }}</td>
                                <td class="text-center">
                                    <a href="{{ route('purchase.files.sellers', $file) }}" class="purchase-sellers-badge" title="View or add sellers for this file">
                                        <i class="bi bi-people" aria-hidden="true"></i>
                                        <span>{{ $file->purchase_items_count }}</span>
                                    </a>
                                </td>
                                <td class="text-center">
                                    <a href="{{ route('purchase.files.documents', $file) }}" class="btn btn-sm btn-outline-theme py-0 px-2" title="Upload or view documents">
                                        <i class="bi bi-cloud-upload" aria-hidden="true"></i>
                                        <span class="ms-1">Upload</span>
                                        @if($file->documents_count > 0)
                                            <span class="badge rounded-pill bg-secondary bg-opacity-25 text-dark border ms-1">{{ $file->documents_count }}</span>
                                        @endif
                                    </a>
                                </td>
                                <td class="text-nowrap">
                                    <a href="{{ route('purchase.files.edit', $file) }}" class="btn btn-sm btn-outline-theme">Edit</a>
                                    <form action="{{ route('purchase.files.destroy', $file) }}" method="post" class="d-inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="button" class="btn btn-sm btn-outline-danger btn-delete-confirm" data-title="Delete purchase file?" data-text="All sellers and documents on this file will be deleted too.">Delete</button>
                                    </form>
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

@push('head')
<style>
    .purchase-sellers-badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.2rem;
        padding: 0.12rem 0.4rem;
        font-size: 0.7rem;
        font-weight: 600;
        line-height: 1;
        border-radius: 999px;
        text-decoration: none;
        color: var(--accent-orange, #f97316);
        background: rgba(249, 115, 22, 0.1);
        border: 1px solid rgba(249, 115, 22, 0.22);
        transition: background 0.15s ease, border-color 0.15s ease;
    }
    .purchase-sellers-badge:hover {
        background: rgba(249, 115, 22, 0.16);
        border-color: rgba(249, 115, 22, 0.35);
        color: var(--accent-orange, #f97316);
    }
    .purchase-sellers-badge .bi {
        font-size: 0.68rem;
        line-height: 1;
        opacity: 0.85;
    }
</style>
@endpush

@push('scripts')
<script>
(function() {
    var form = document.getElementById('purchase-files-filter-form');
    var btn = document.getElementById('purchase-files-search-btn');
    var spinner = document.getElementById('purchase-files-search-spinner');
    if (!form || !btn || !spinner) return;
    form.addEventListener('submit', function() {
        btn.disabled = true;
        spinner.classList.remove('d-none');
    });
})();
</script>
@endpush
