@extends('layouts.app')

@section('title', 'Projects')

@push('head')
<style>
    .project-drag-handle {
        cursor: grab;
        color: #94a3b8;
        padding: 0.25rem;
        line-height: 1;
        touch-action: none;
    }
    .project-drag-handle:active {
        cursor: grabbing;
    }
    .project-sortable-ghost {
        opacity: 0.45;
        background: rgba(249, 115, 22, 0.08) !important;
    }
    .project-sortable-chosen {
        background: rgba(249, 115, 22, 0.06) !important;
    }
    #projects-sortable-tbody tr[data-project-id] {
        transition: background-color 0.15s ease;
    }
    .projects-reorder-hint {
        font-size: 0.875rem;
        color: #64748b;
    }
    .projects-reorder-status {
        font-size: 0.875rem;
        min-height: 1.25rem;
    }
    .projects-reorder-status.is-saving {
        color: #64748b;
    }
    .projects-reorder-status.is-saved {
        color: #15803d;
    }
    .projects-reorder-status.is-error {
        color: #b91c1c;
    }
</style>
@endpush

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
            @if($canReorder ?? false)
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                    <p class="projects-reorder-hint mb-0">
                        <i class="bi bi-arrows-move me-1" aria-hidden="true"></i>
                        Drag rows to reorder projects. Changes save automatically.
                    </p>
                    <div id="projects-reorder-status" class="projects-reorder-status" role="status" aria-live="polite"></div>
                </div>
            @elseif(($search ?? '') !== '')
                <p class="projects-reorder-hint mb-3">Clear search to reorder projects by drag and drop.</p>
            @endif

            <div class="table-responsive">
                <table class="table table-striped table-theme mb-0 align-middle">
                    <thead>
                        <tr>
                            @if($canReorder ?? false)
                                <th style="width: 40px;" aria-label="Drag to reorder"></th>
                            @endif
                            <th style="width: 56px;">#</th>
                            <th>Name</th>
                            <th>Land type</th>
                            <th class="text-center" style="width: 90px;">Files</th>
                            <th class="text-center" style="width: 90px;">Partners</th>
                            <th style="width: 260px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="projects-sortable-tbody">
                        @foreach($projects as $project)
                            <tr data-project-id="{{ $project->id }}">
                                @if($canReorder ?? false)
                                    <td>
                                        <button type="button" class="btn btn-link p-0 border-0 project-drag-handle" aria-label="Drag to reorder {{ $project->name }}" tabindex="-1">
                                            <i class="bi bi-grip-vertical fs-5" aria-hidden="true"></i>
                                        </button>
                                    </td>
                                @endif
                                <td class="project-row-num">{{ $loop->iteration }}</td>
                                <td class="fw-semibold">{{ $project->name }}</td>
                                <td class="small text-muted">{{ $project->landType?->name ?? '—' }}</td>
                                <td class="text-center">
                                    <a href="{{ route('purchase.files.index', ['project' => $project->id]) }}" class="badge badge-pink rounded-pill text-decoration-none fw-semibold px-3 py-2" title="Purchase files">
                                        {{ $project->purchase_files_count }}
                                    </a>
                                </td>
                                <td class="text-center">
                                    <a href="{{ route('projects.partners', $project) }}" class="badge rounded-pill text-bg-light border text-decoration-none fw-semibold px-3 py-2" title="Manage partners">
                                        {{ $project->parties_count }}
                                    </a>
                                </td>
                                <td class="text-nowrap">
                                    <a href="{{ route('projects.show', $project) }}" class="btn btn-sm btn-outline-theme">View</a>
                                    <a href="{{ route('projects.partners', $project) }}" class="btn btn-sm btn-pink">Partners</a>
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

            @if(!($canReorder ?? false) && method_exists($projects, 'links'))
                <div class="mt-3">{{ $projects->links() }}</div>
            @endif
        @endif
    </div>
</div>
@endsection

@push('scripts')
@if($canReorder ?? false)
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.6/Sortable.min.js"></script>
@endif
<script>
(function() {
    var form = document.getElementById('projects-search-form');
    var btn = document.getElementById('projects-search-btn');
    var spinner = document.getElementById('projects-search-spinner');
    if (form && btn && spinner) {
        form.addEventListener('submit', function() {
            btn.disabled = true;
            spinner.classList.remove('d-none');
        });
    }

    @if($canReorder ?? false)
    var tbody = document.getElementById('projects-sortable-tbody');
    var statusEl = document.getElementById('projects-reorder-status');
    var token = document.querySelector('meta[name="csrf-token"]');
    if (!tbody || !token || typeof Sortable === 'undefined') return;

    var saveTimer = null;
    var lastSavedOrder = null;

    function currentOrder() {
        return Array.from(tbody.querySelectorAll('tr[data-project-id]')).map(function (tr) {
            return parseInt(tr.getAttribute('data-project-id'), 10);
        });
    }

    function updateRowNumbers() {
        tbody.querySelectorAll('tr[data-project-id]').forEach(function (tr, index) {
            var cell = tr.querySelector('.project-row-num');
            if (cell) cell.textContent = String(index + 1);
        });
    }

    function setStatus(state, message) {
        if (!statusEl) return;
        statusEl.className = 'projects-reorder-status' + (state ? ' is-' + state : '');
        statusEl.textContent = message || '';
    }

    function saveOrder() {
        var order = currentOrder();
        if (lastSavedOrder && order.join(',') === lastSavedOrder.join(',')) {
            return;
        }

        setStatus('saving', 'Saving order…');

        fetch(@json(route('projects.reorder')), {
            method: 'PATCH',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': token.getAttribute('content'),
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({ order: order })
        })
            .then(function (res) {
                return res.json().then(function (data) {
                    return { ok: res.ok, data: data };
                }).catch(function () {
                    return { ok: false, data: {} };
                });
            })
            .then(function (result) {
                if (result.ok) {
                    lastSavedOrder = order.slice();
                    setStatus('saved', 'Order saved');
                    window.setTimeout(function () {
                        if (statusEl && statusEl.textContent === 'Order saved') {
                            setStatus('', '');
                        }
                    }, 2000);
                    return;
                }

                var msg = (result.data && result.data.message) ? result.data.message : 'Could not save order.';
                setStatus('error', msg);
            })
            .catch(function () {
                setStatus('error', 'Could not save order.');
            });
    }

    lastSavedOrder = currentOrder();

    Sortable.create(tbody, {
        handle: '.project-drag-handle',
        animation: 150,
        ghostClass: 'project-sortable-ghost',
        chosenClass: 'project-sortable-chosen',
        draggable: 'tr[data-project-id]',
        onEnd: function () {
            updateRowNumbers();
            if (saveTimer) window.clearTimeout(saveTimer);
            saveTimer = window.setTimeout(saveOrder, 250);
        }
    });
    @endif
})();
</script>
@endpush
