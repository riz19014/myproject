@extends('layouts.app')

@section('title', 'Factory')

@push('head')
<style>
    .factory-partners-badge {
        cursor: pointer;
        border: 0;
        line-height: 1.2;
    }
    .factory-partners-popover {
        max-width: min(320px, calc(100vw - 2rem));
    }
    .factory-partners-popover .popover-header {
        font-size: 0.85rem;
        font-weight: 700;
        background: #f8fafc;
        border-bottom: 1px solid #e2e8f0;
    }
    .factory-partners-popover .popover-body {
        font-size: 0.875rem;
        color: #334155;
        padding: 0.65rem 0.85rem;
    }
    .factory-partners-popover__list {
        margin: 0;
        padding-left: 1.1rem;
    }
    .factory-partners-popover__list li + li {
        margin-top: 0.3rem;
    }
</style>
@endpush

@section('content')
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
    <div>
        <h1 class="mb-1 d-flex align-items-center gap-2">
            <i class="bi bi-building" aria-hidden="true"></i>
            <span>Factory</span>
        </h1>
        <p class="text-muted small mb-0">Projects whose land type is Factory.</p>
    </div>
    <a href="{{ route('projects.create') }}" class="btn btn-outline-theme">Add project</a>
</div>

<div class="card card-theme">
    <div class="card-body">
        <h2 class="h5 mb-3">Factory projects</h2>
        @if($projects->isEmpty())
            <p class="text-muted mb-0">No factory projects yet. <a href="{{ route('projects.create') }}">Add a project</a> and set its land type to Factory.</p>
        @else
            <div class="table-responsive">
                <table class="table table-striped table-theme mb-0 align-middle">
                    <thead>
                        <tr>
                            <th style="width: 56px;">#</th>
                            <th>Project</th>
                            <th>Land type</th>
                            <th class="text-center" style="width: 110px;">Partners</th>
                            <th style="width: 140px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($projects as $project)
                            @php
                                $partnerNames = $project->parties->pluck('name')->filter()->values();
                                $partnerCount = (int) $project->parties_count;
                            @endphp
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td class="fw-semibold"><x-project-name :project="$project" /></td>
                                <td class="small text-muted">{{ $project->landType?->name ?? '—' }}</td>
                                <td class="text-center">
                                    @if($partnerCount > 0)
                                        <div id="factory-partners-popover-{{ $project->id }}" class="d-none">
                                            <ul class="factory-partners-popover__list">
                                                @foreach($partnerNames as $name)
                                                    <li>{{ $name }}</li>
                                                @endforeach
                                            </ul>
                                        </div>
                                        <button
                                            type="button"
                                            class="badge rounded-pill text-bg-light border fw-semibold px-3 py-2 factory-partners-badge"
                                            data-factory-partners-btn
                                            data-popover-content="#factory-partners-popover-{{ $project->id }}"
                                            aria-label="{{ $partnerCount }} partners — click to view names"
                                        >
                                            {{ $partnerCount }}
                                        </button>
                                    @else
                                        <span class="badge rounded-pill text-bg-light border text-muted fw-semibold px-3 py-2">0</span>
                                    @endif
                                </td>
                                <td>
                                    <a href="{{ route('projects.show', $project) }}" class="btn btn-sm btn-pink">View</a>
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

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    if (typeof bootstrap === 'undefined' || !bootstrap.Popover) return;

    var buttons = document.querySelectorAll('[data-factory-partners-btn]');
    var instances = [];

    buttons.forEach(function (btn) {
        var selector = btn.getAttribute('data-popover-content');
        var contentEl = selector ? document.querySelector(selector) : null;
        if (!contentEl) return;

        var popover = new bootstrap.Popover(btn, {
            title: 'Partners',
            content: contentEl.innerHTML,
            html: true,
            trigger: 'click',
            placement: 'bottom',
            customClass: 'factory-partners-popover',
            sanitize: false
        });
        instances.push({ btn: btn, popover: popover });
    });

    document.addEventListener('click', function (e) {
        instances.forEach(function (item) {
            var tip = item.popover.tip;
            if (!item.btn.contains(e.target) && !(tip && tip.contains(e.target))) {
                item.popover.hide();
            }
        });
    });
});
</script>
@endpush
