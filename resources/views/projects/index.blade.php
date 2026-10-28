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
    .project-name-status {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 0.5rem 0.75rem;
    }
    .project-status-select {
        min-width: 8.5rem;
        max-width: 11rem;
        font-size: 0.8125rem;
        font-weight: 600;
        padding: 0.25rem 0.5rem;
        border-radius: 0.375rem;
        border: 1px solid rgba(15, 23, 42, 0.14);
        background: #fff;
        color: #0f172a;
        line-height: 1.3;
    }
    .project-status-select.is-in_progress {
        border-color: rgba(37, 99, 235, 0.35);
        background: #eff6ff;
        color: #1d4ed8;
    }
    .project-status-select.is-under_review {
        border-color: rgba(217, 119, 6, 0.4);
        background: #fffbeb;
        color: #b45309;
    }
    .project-status-select.is-completed {
        border-color: rgba(22, 163, 74, 0.35);
        background: #f0fdf4;
        color: #15803d;
    }
    .project-status-select.is-saving {
        opacity: 0.7;
    }
    .project-status-widgets {
        margin-bottom: 1.5rem;
    }
    .project-status-widget {
        display: flex;
        flex-direction: column;
        align-items: flex-start;
        text-align: left;
        height: 100%;
        padding: 1.35rem 1.25rem;
        border-radius: 14px;
        border: 1px solid var(--border-dark);
        background: var(--card-bg);
        text-decoration: none;
        color: inherit;
        transition: border-color 0.2s, box-shadow 0.2s, transform 0.15s;
    }
    .project-status-widget:hover {
        border-color: rgba(249, 115, 22, 0.45);
        box-shadow: 0 12px 28px rgba(249, 115, 22, 0.12);
        transform: translateY(-2px);
        color: inherit;
    }
    .project-status-widget.is-active {
        border-color: rgba(249, 115, 22, 0.55);
        box-shadow: 0 10px 24px rgba(249, 115, 22, 0.14);
    }
    .project-status-widget__icon {
        width: 2.75rem;
        height: 2.75rem;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.25rem;
        margin-bottom: 0.85rem;
    }
    .project-status-widget__icon--in_progress {
        background: rgba(37, 99, 235, 0.12);
        color: #1d4ed8;
    }
    .project-status-widget__icon--under_review {
        background: rgba(217, 119, 6, 0.12);
        color: #b45309;
    }
    .project-status-widget__icon--completed {
        background: rgba(22, 163, 74, 0.12);
        color: #15803d;
    }
    .project-status-widget__title {
        font-size: 1.05rem;
        font-weight: 600;
        margin-bottom: 0.2rem;
    }
    .project-status-widget__count {
        font-size: 1.5rem;
        font-weight: 800;
        letter-spacing: -0.02em;
        line-height: 1.2;
        margin-bottom: 0.35rem;
    }
    .project-status-widget__desc {
        font-size: 0.8125rem;
        color: var(--text-muted);
        line-height: 1.4;
        margin: 0;
    }
    .project-status-widget__arrow {
        margin-top: auto;
        padding-top: 0.85rem;
        font-size: 0.8rem;
        font-weight: 600;
        color: var(--accent-orange);
    }
</style>
@endpush

@section('content')
@php
    $statusCounts = $statusCounts ?? [
        \App\Models\Project::STATUS_IN_PROGRESS => 0,
        \App\Models\Project::STATUS_UNDER_REVIEW => 0,
        \App\Models\Project::STATUS_COMPLETED => 0,
    ];
    $statusFilter = $statusFilter ?? '';
    $statusWidgets = [
        \App\Models\Project::STATUS_IN_PROGRESS => [
            'label' => 'In progress',
            'desc' => 'Projects currently being worked on.',
            'icon' => 'bi-hourglass-split',
        ],
        \App\Models\Project::STATUS_UNDER_REVIEW => [
            'label' => 'Under Review',
            'desc' => 'Projects waiting for review or decision.',
            'icon' => 'bi-eye',
        ],
        \App\Models\Project::STATUS_COMPLETED => [
            'label' => 'Completed',
            'desc' => 'Finished projects.',
            'icon' => 'bi-check2-circle',
        ],
    ];
@endphp
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
    <h1 class="mb-0">Projects</h1>
    <a href="{{ route('projects.create') }}" class="btn btn-pink">Add Project</a>
</div>

<div class="card card-theme mb-4">
    <div class="card-body">
        <form method="get" action="{{ route('projects.index') }}" id="projects-search-form" class="row g-2 align-items-end">
            @if($statusFilter !== '')
                <input type="hidden" name="status" value="{{ $statusFilter }}">
            @endif
            <div class="col-md-9 col-lg-10">
                <label for="q" class="form-label">Search projects</label>
                <input type="search" name="q" id="q" class="form-control form-control-theme" value="{{ $search ?? '' }}" placeholder="Project name or land type…" autocomplete="off">
            </div>
            <div class="col-md-3 col-lg-2 d-flex flex-wrap gap-2">
                <button type="submit" class="btn btn-pink flex-grow-1" id="projects-search-btn">
                    <span class="spinner-border spinner-border-sm me-1 d-none" id="projects-search-spinner" role="status" aria-hidden="true"></span>
                    Search
                </button>
                @if(($search ?? '') !== '' || $statusFilter !== '')
                    <a href="{{ route('projects.index') }}" class="btn btn-outline-theme">Clear</a>
                @endif
            </div>
        </form>
    </div>
</div>

<div class="row g-3 g-lg-4 project-status-widgets">
    @foreach($statusWidgets as $statusKey => $widget)
        @php
            $isActive = $statusFilter === $statusKey;
            $widgetUrl = $isActive
                ? route('projects.index', array_filter(['q' => $search ?: null]))
                : route('projects.index', array_filter(['status' => $statusKey, 'q' => $search ?: null]));
        @endphp
        <div class="col-md-4">
            <a href="{{ $widgetUrl }}" class="project-status-widget {{ $isActive ? 'is-active' : '' }}">
                <span class="project-status-widget__icon project-status-widget__icon--{{ $statusKey }}" aria-hidden="true">
                    <i class="bi {{ $widget['icon'] }}"></i>
                </span>
                <span class="project-status-widget__title">{{ $widget['label'] }}</span>
                <span class="project-status-widget__count">{{ number_format((int) ($statusCounts[$statusKey] ?? 0)) }}</span>
                <p class="project-status-widget__desc">{{ $widget['desc'] }}</p>
                <span class="project-status-widget__arrow">
                    {{ $isActive ? 'Clear filter' : 'View projects' }}
                    <i class="bi bi-chevron-right" aria-hidden="true"></i>
                </span>
            </a>
        </div>
    @endforeach
</div>

<div class="card card-theme">
    <div class="card-body">
        @if($projects->isEmpty())
            @if(($search ?? '') !== '' || ($statusFilter ?? '') !== '')
                <p class="text-muted mb-0">No projects match your filters. <a href="{{ route('projects.index') }}">Clear filters</a> or <a href="{{ route('projects.create') }}">create a project</a>.</p>
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
            @elseif(($search ?? '') !== '' || ($statusFilter ?? '') !== '')
                <p class="projects-reorder-hint mb-3">Clear filters to reorder projects by drag and drop.</p>
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
                                <td>
                                    @php($projectStatus = $project->status ?: \App\Models\Project::STATUS_IN_PROGRESS)
                                    <div class="project-name-status">
                                        <span class="fw-semibold"><x-project-name :project="$project" /></span>
                                        <label class="visually-hidden" for="project-status-{{ $project->id }}">Status for {{ $project->name }}</label>
                                        <select
                                            id="project-status-{{ $project->id }}"
                                            class="form-select form-select-sm project-status-select is-{{ $projectStatus }}"
                                            data-project-status
                                            data-project-id="{{ $project->id }}"
                                            data-update-url="{{ route('projects.status.update', $project) }}"
                                            aria-label="Project status"
                                        >
                                            @foreach(\App\Models\Project::statusOptions() as $value => $label)
                                                <option value="{{ $value }}" @selected($projectStatus === $value)>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </td>
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

(function () {
    var token = document.querySelector('meta[name="csrf-token"]');
    if (!token) return;

    function syncSelectClass(select) {
        select.classList.remove('is-in_progress', 'is-under_review', 'is-completed');
        if (select.value) select.classList.add('is-' + select.value);
    }

    function ensureToastContainer() {
        var container = document.querySelector('.app-toast-container');
        if (container) return container;
        container = document.createElement('div');
        container.className = 'toast-container position-fixed bottom-0 end-0 p-3 app-toast-container';
        container.setAttribute('aria-live', 'polite');
        container.setAttribute('aria-atomic', 'true');
        document.body.appendChild(container);
        return container;
    }

    function showStatusToast(message, isError) {
        if (!window.bootstrap || !bootstrap.Toast) {
            if (isError) alert(message);
            return;
        }
        var container = ensureToastContainer();
        var el = document.createElement('div');
        el.className = 'toast border-0 ' + (isError ? 'text-bg-danger' : 'app-success-toast');
        el.setAttribute('role', 'alert');
        el.setAttribute('data-bs-delay', '4000');
        el.setAttribute('data-bs-autohide', 'true');
        el.innerHTML =
            '<div class="d-flex align-items-start">' +
                '<div class="toast-body flex-grow-1"></div>' +
                '<button type="button" class="btn-close ' + (isError ? 'btn-close-white' : 'btn-close-white') + ' flex-shrink-0 mt-2 me-2" data-bs-dismiss="toast" aria-label="Close"></button>' +
            '</div>';
        el.querySelector('.toast-body').textContent = message;
        container.appendChild(el);
        var toast = bootstrap.Toast.getOrCreateInstance(el);
        el.addEventListener('hidden.bs.toast', function () {
            toast.dispose();
            el.remove();
        });
        toast.show();
    }

    document.querySelectorAll('select[data-project-status]').forEach(function (select) {
        var previous = select.value;
        select.addEventListener('change', function () {
            var url = select.getAttribute('data-update-url');
            if (!url) return;
            var selectedLabel = select.options[select.selectedIndex]
                ? select.options[select.selectedIndex].text
                : select.value;
            select.classList.add('is-saving');
            select.disabled = true;

            fetch(url, {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': token.getAttribute('content'),
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({ status: select.value })
            })
                .then(function (res) {
                    return res.json().then(function (data) {
                        return { ok: res.ok, data: data };
                    }).catch(function () {
                        return { ok: res.ok, data: {} };
                    });
                })
                .then(function (result) {
                    if (!result.ok) {
                        select.value = previous;
                        syncSelectClass(select);
                        showStatusToast(
                            (result.data && result.data.message) ? result.data.message : 'Could not update status.',
                            true
                        );
                        return;
                    }
                    previous = select.value;
                    syncSelectClass(select);
                    var label = (result.data && result.data.status_label) ? result.data.status_label : selectedLabel;
                    showStatusToast('Status updated to ' + label + '.', false);
                })
                .catch(function () {
                    select.value = previous;
                    syncSelectClass(select);
                    showStatusToast('Could not update status.', true);
                })
                .finally(function () {
                    select.disabled = false;
                    select.classList.remove('is-saving');
                });
        });
    });
})();
</script>
@endpush
