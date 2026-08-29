@extends('layouts.app')

@section('title', ($collective['total_land_sheet'] ?? $collective['name']).' — '.$project->name)

@push('head')
<style>
    .collective-sheet {
        border: 2px solid #334155;
        border-radius: 12px;
        overflow: hidden;
        background: #fff;
    }
    .collective-sheet__head {
        display: grid;
        grid-template-columns: minmax(5.5rem, auto) 1fr minmax(9rem, 1.2fr);
        gap: 0;
        border-bottom: 2px solid #334155;
        background: #f8fafc;
    }
    .collective-sheet__head-cell {
        padding: 0.75rem 0.9rem;
        display: flex;
        flex-direction: column;
        justify-content: center;
        gap: 0.15rem;
        min-width: 0;
    }
    .collective-sheet__head-cell + .collective-sheet__head-cell {
        border-left: 2px solid #334155;
    }
    .collective-sheet__head-label {
        font-size: 0.68rem;
        font-weight: 700;
        letter-spacing: 0.04em;
        text-transform: uppercase;
        color: #64748b;
    }
    .collective-sheet__head-value {
        font-size: 1rem;
        font-weight: 800;
        color: #0f172a;
        line-height: 1.25;
        word-break: break-word;
    }
    .collective-sheet__table {
        width: 100%;
        margin: 0;
        border-collapse: collapse;
    }
    .collective-sheet__table th,
    .collective-sheet__table td {
        border: 1px solid #cbd5e1;
        padding: 0.65rem 0.75rem;
        vertical-align: middle;
    }
    .collective-sheet__table thead th {
        background: #f1f5f9;
        font-size: 0.78rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.03em;
        color: #334155;
        text-align: center;
    }
    .collective-sheet__files-cell {
        font-weight: 700;
        color: #0f172a;
        white-space: nowrap;
    }
    .collective-sheet__files-cell .collective-sheet__eq {
        color: #64748b;
        font-weight: 600;
        margin: 0 0.25rem;
    }
    .collective-sheet__sold-cell {
        text-align: center;
        font-weight: 700;
        font-variant-numeric: tabular-nums;
    }
    .collective-sheet__sold-cell.is-done {
        color: #15803d;
    }
    .collective-sheet__bal-cell {
        text-align: center;
        font-weight: 700;
        font-variant-numeric: tabular-nums;
        color: #9a3412;
    }
    .collective-sheet__row--residential td:first-child {
        box-shadow: inset 3px 0 0 #2563eb;
    }
    .collective-sheet__row--commercial td:first-child {
        box-shadow: inset 3px 0 0 #c026d3;
    }
    .collective-sheet__next {
        border-top: 2px solid #334155;
        padding: 0.85rem 1rem 1rem;
        background: #fffbeb;
    }
    .collective-sheet__next-title {
        font-size: 0.78rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        color: #92400e;
        margin-bottom: 0.45rem;
    }
    .collective-sheet__next-box {
        border: 2px dashed #f59e0b;
        border-radius: 10px;
        min-height: 3.25rem;
        padding: 0.75rem 0.9rem;
        background: #fff;
        display: flex;
        align-items: center;
        gap: 0.75rem;
        flex-wrap: wrap;
    }
    .collective-sheet__next-land {
        font-size: 1.05rem;
        font-weight: 800;
        color: #0f172a;
    }
    .collective-sheet__next-note {
        font-size: 0.82rem;
        color: #78716c;
    }
    @media (max-width: 767.98px) {
        .collective-sheet__head {
            grid-template-columns: 1fr;
        }
        .collective-sheet__head-cell + .collective-sheet__head-cell {
            border-left: 0;
            border-top: 1px solid #cbd5e1;
        }
    }

    .collective-exemption-bar {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        gap: 0.75rem 1rem;
        padding: 0.9rem 1rem;
        margin-bottom: 1.25rem;
        border: 1px solid rgba(15, 23, 42, 0.1);
        border-radius: 14px;
        background: linear-gradient(180deg, #f8fafc 0%, #fff 100%);
    }
    .collective-exemption-bar__label {
        display: block;
        font-size: 0.68rem;
        font-weight: 800;
        letter-spacing: 0.06em;
        text-transform: uppercase;
        color: #64748b;
        margin-bottom: 0.2rem;
    }
    .collective-exemption-bar__value {
        font-size: 0.92rem;
        font-weight: 650;
        color: #0f172a;
    }
    .collective-exemption-bar__value-btn {
        display: block;
        margin: 0;
        padding: 0;
        border: 0;
        background: transparent;
        text-align: left;
        cursor: pointer;
        font: inherit;
        font-size: 0.92rem;
        font-weight: 650;
        color: #0f172a;
    }
    .collective-exemption-bar__value-btn:hover,
    .collective-exemption-bar__value-btn:focus-visible {
        color: #9a3412;
        text-decoration: underline;
    }
    .collective-exemption-bar__view {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        margin-top: 0.45rem;
        padding: 0;
        border: 0;
        background: transparent;
        color: #c2410c;
        font-size: 0.82rem;
        font-weight: 700;
        cursor: pointer;
    }
    .collective-exemption-bar__view:hover,
    .collective-exemption-bar__view:focus-visible {
        color: #9a3412;
        text-decoration: underline;
    }

    .exemption-view-meta {
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem 1rem;
        margin-bottom: 1rem;
        font-size: 0.85rem;
        color: #475569;
    }
    .exemption-view-component {
        border: 1px solid rgba(15, 23, 42, 0.1);
        border-radius: 14px;
        padding: 0.9rem 1rem;
        margin-bottom: 0.75rem;
        background: #fff;
    }
    .exemption-view-component__head {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        gap: 0.4rem 0.75rem;
        margin-bottom: 0.65rem;
    }
    .exemption-view-component__title {
        margin: 0;
        font-size: 0.98rem;
        font-weight: 800;
        color: #0f172a;
    }
    .exemption-view-component__pct {
        font-size: 0.78rem;
        font-weight: 800;
        letter-spacing: 0.02em;
        color: #9a3412;
        background: #ffedd5;
        border-radius: 999px;
        padding: 0.2rem 0.55rem;
    }
    .exemption-view-plots {
        width: 100%;
        margin: 0;
        border-collapse: collapse;
        font-size: 0.84rem;
    }
    .exemption-view-plots th,
    .exemption-view-plots td {
        padding: 0.4rem 0.35rem;
        border-top: 1px solid #e2e8f0;
        text-align: left;
        vertical-align: top;
    }
    .exemption-view-plots th {
        font-size: 0.7rem;
        font-weight: 800;
        letter-spacing: 0.04em;
        text-transform: uppercase;
        color: #64748b;
        border-top: 0;
    }
    .exemption-view-plots td:not(:first-child),
    .exemption-view-plots th:not(:first-child) {
        text-align: right;
        white-space: nowrap;
    }
    .exemption-view-empty {
        padding: 1.25rem 1rem;
        text-align: center;
        color: #64748b;
        font-size: 0.9rem;
        border: 1px dashed rgba(15, 23, 42, 0.15);
        border-radius: 12px;
        background: #f8fafc;
    }

    #collectiveExemptionModal .modal-content,
    #activeExemptionViewModal .modal-content {
        border: 1px solid rgba(15, 23, 42, 0.1);
        border-radius: 18px;
        overflow: hidden;
        box-shadow: 0 24px 60px rgba(15, 23, 42, 0.2);
    }
    #collectiveExemptionModal .modal-header,
    #activeExemptionViewModal .modal-header {
        background: linear-gradient(135deg, #fff7ed 0%, #ffedd5 55%, #fff 100%);
        border-bottom: 1px solid rgba(15, 23, 42, 0.08);
        padding: 1.15rem 1.35rem;
    }
    #collectiveExemptionModal .modal-title,
    #activeExemptionViewModal .modal-title {
        font-weight: 800;
        letter-spacing: -0.02em;
        color: #0f172a;
    }
    #collectiveExemptionModal .modal-body {
        padding: 1.2rem 1.35rem 0.5rem;
        background: #f8fafc;
    }
    #collectiveExemptionModal .modal-footer {
        border-top: 1px solid rgba(15, 23, 42, 0.08);
        background: #fff;
        padding: 1rem 1.35rem 1.2rem;
    }
    .exemption-pick-hint {
        font-size: 0.88rem;
        color: #64748b;
        margin-bottom: 0.95rem;
    }
    .exemption-pick-list {
        display: flex;
        flex-direction: column;
        gap: 0.75rem;
        max-height: min(58vh, 460px);
        overflow: auto;
        padding-bottom: 0.75rem;
    }
    .exemption-pick-option {
        position: relative;
        margin: 0;
        cursor: pointer;
    }
    .exemption-pick-option input {
        position: absolute;
        opacity: 0;
        pointer-events: none;
    }
    .exemption-pick-card {
        border: 1.5px solid rgba(15, 23, 42, 0.12);
        border-radius: 14px;
        background: #fff;
        padding: 0.95rem 1rem 1rem;
        transition: border-color 0.15s ease, box-shadow 0.15s ease, background 0.15s ease;
    }
    .exemption-pick-option:hover .exemption-pick-card {
        border-color: rgba(249, 115, 22, 0.35);
        box-shadow: 0 8px 22px rgba(15, 23, 42, 0.06);
    }
    .exemption-pick-option input:checked + .exemption-pick-card {
        border-color: rgba(249, 115, 22, 0.7);
        background: #fff7ed;
        box-shadow: 0 0 0 3px rgba(249, 115, 22, 0.14);
    }
    .exemption-pick-card__top {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 0.75rem;
        margin-bottom: 0.45rem;
    }
    .exemption-pick-card__title {
        font-size: 0.98rem;
        font-weight: 800;
        color: #0f172a;
        margin: 0;
    }
    .exemption-pick-card__badge {
        flex: 0 0 auto;
        display: inline-flex;
        align-items: center;
        padding: 0.18rem 0.55rem;
        border-radius: 999px;
        font-size: 0.7rem;
        font-weight: 750;
        letter-spacing: 0.02em;
        background: #e2e8f0;
        color: #334155;
    }
    .exemption-pick-card__badge.is-live {
        background: #dcfce7;
        color: #166534;
    }
    .exemption-pick-card__summary {
        font-size: 0.9rem;
        font-weight: 650;
        color: #334155;
        margin-bottom: 0.45rem;
    }
    .exemption-pick-card__meta {
        font-size: 0.78rem;
        color: #64748b;
        margin-bottom: 0.55rem;
    }
    .exemption-pick-chips {
        display: flex;
        flex-wrap: wrap;
        gap: 0.35rem;
    }
    .exemption-pick-chip {
        display: inline-flex;
        align-items: center;
        gap: 0.25rem;
        padding: 0.2rem 0.55rem;
        border-radius: 999px;
        background: #f1f5f9;
        color: #334155;
        font-size: 0.72rem;
        font-weight: 700;
    }
    .exemption-pick-option input:checked + .exemption-pick-card .exemption-pick-chip {
        background: #ffedd5;
        color: #9a3412;
    }
    .exemption-pick-breakdown {
        display: flex;
        flex-direction: column;
        gap: 0.55rem;
        margin-top: 0.15rem;
    }
    .exemption-pick-comp {
        border: 1px solid rgba(15, 23, 42, 0.08);
        border-radius: 10px;
        background: #fff;
        padding: 0.55rem 0.7rem;
    }
    .exemption-pick-option input:checked + .exemption-pick-card .exemption-pick-comp {
        border-color: rgba(194, 65, 12, 0.25);
        background: #fff7ed;
    }
    .exemption-pick-comp__head {
        display: flex;
        flex-wrap: wrap;
        align-items: baseline;
        justify-content: space-between;
        gap: 0.25rem 0.75rem;
        margin-bottom: 0.35rem;
    }
    .exemption-pick-comp__title {
        font-size: 0.82rem;
        font-weight: 800;
        color: #0f172a;
    }
    .exemption-pick-comp__pool {
        font-size: 0.74rem;
        font-weight: 700;
        color: #9a3412;
    }
    .exemption-pick-comp__plots {
        margin: 0;
        padding: 0;
        list-style: none;
        display: flex;
        flex-direction: column;
        gap: 0.2rem;
    }
    .exemption-pick-comp__plots li {
        display: flex;
        flex-wrap: wrap;
        justify-content: space-between;
        gap: 0.25rem 0.75rem;
        font-size: 0.76rem;
        color: #475569;
    }
    .exemption-pick-comp__plots strong {
        color: #0f172a;
        font-weight: 700;
    }
    .exemption-pick-comp__empty {
        font-size: 0.74rem;
        color: #94a3b8;
    }
    .exemption-pick-empty {
        border: 1px dashed rgba(15, 23, 42, 0.15);
        border-radius: 12px;
        padding: 1rem;
        background: #fff;
        color: #64748b;
        font-size: 0.9rem;
    }
</style>
@endpush

@php
    $balanceRows = $collective['balance_rows'] ?? ($collective['formula_columns'] ?? []);
    $landLabel = $collective['total_land_sheet'] ?? ($collective['total_land_area'] ?? '—');
@endphp

@section('content')
@if(session('success'))
    <div class="alert alert-success small">{{ session('success') }}</div>
@endif
@if($errors->any())
    <div class="alert alert-danger small">
        <ul class="mb-0 ps-3">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
    <div>
        <h1 class="mb-1">{{ $landLabel }}</h1>
        <p class="text-muted small mb-0">
            Project: <strong><x-project-name :project="$project" /></strong>
            · {{ $collective['name'] }}
            @if($collective['is_open'])
                <span class="badge text-bg-success ms-1">Open</span>
            @else
                <span class="badge text-bg-secondary ms-1">Completed</span>
            @endif
        </p>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <a href="{{ route('sale.files.index', $project) }}" class="btn btn-outline-theme">Back to File Sale</a>
        @if($collective['is_open'])
            <form method="post" action="{{ route('sale.files.collectives.complete', [$project, $collective['id']]) }}"
                  onsubmit="return confirm('Mark {{ $collective['name'] }} complete? It will no longer accept new files.');">
                @csrf
                <button type="submit" class="btn btn-outline-theme">Mark complete</button>
            </form>
        @else
            <form method="post" action="{{ route('sale.files.collectives.reopen', [$project, $collective['id']]) }}">
                @csrf
                <button type="submit" class="btn btn-outline-secondary">Reopen</button>
            </form>
        @endif
    </div>
</div>

<div class="card card-theme mb-4">
    <div class="card-body">
        <div class="collective-exemption-bar">
            <div>
                <span class="collective-exemption-bar__label">Active exemption</span>
                <button
                    type="button"
                    class="collective-exemption-bar__value-btn"
                    data-bs-toggle="modal"
                    data-bs-target="#activeExemptionViewModal"
                    title="View full exemption"
                >
                    {{ $collective['exemption_summary'] }}
                    · 1 acre = {{ rtrim(rtrim(number_format((float) ($collective['marla_per_acre'] ?? 160), 4, '.', ''), '0'), '.') }}M
                </button>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <button
                    type="button"
                    class="btn btn-outline-theme"
                    data-bs-toggle="modal"
                    data-bs-target="#activeExemptionViewModal"
                >
                    <i class="bi bi-eye" aria-hidden="true"></i>
                    View
                </button>
                <button
                    type="button"
                    class="btn btn-pink"
                    data-bs-toggle="modal"
                    data-bs-target="#collectiveExemptionModal"
                >
                    <i class="bi bi-sliders" aria-hidden="true"></i>
                    Choose exemption
                </button>
                @if($project->isDha())
                    <a
                        href="{{ route('sale.projects.exemption.edit', ['project' => $project, 'return_collective_id' => $collective['id']]) }}"
                        class="btn btn-outline-theme"
                    >
                        <i class="bi bi-plus-lg" aria-hidden="true"></i>
                        Add exemption
                    </a>
                @endif
            </div>
        </div>

        <div class="collective-sheet mb-4">
            <div class="collective-sheet__head">
                <div class="collective-sheet__head-cell">
                    <span class="collective-sheet__head-label">Files</span>
                    <span class="collective-sheet__head-value">{{ $collective['file_count'] }} {{ $collective['file_count'] === 1 ? 'file' : 'files' }}</span>
                </div>
                <div class="collective-sheet__head-cell">
                    <span class="collective-sheet__head-label">Total land</span>
                    <span class="collective-sheet__head-value">{{ $landLabel }}</span>
                </div>
                <div class="collective-sheet__head-cell">
                    <span class="collective-sheet__head-label">File name</span>
                    <span class="collective-sheet__head-value">{{ $collective['name'] }} = {{ $landLabel }}</span>
                </div>
            </div>

            @if($balanceRows !== [])
                <div class="table-responsive">
                    <table class="collective-sheet__table">
                        <thead>
                            <tr>
                                <th style="width: 34%;">Files</th>
                                <th style="width: 33%;">Sold with payment file</th>
                                <th style="width: 33%;">File Bal</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($balanceRows as $row)
                                @php
                                    $componentSlug = (string) ($row['component_slug'] ?? '');
                                    $rowClass = str_contains($componentSlug, 'commercial')
                                        ? 'collective-sheet__row--commercial'
                                        : (str_contains($componentSlug, 'residential') ? 'collective-sheet__row--residential' : '');
                                @endphp
                                <tr class="{{ $rowClass }}">
                                    <td class="collective-sheet__files-cell">
                                        {{ $row['short_label'] }}
                                        <span class="collective-sheet__eq">=</span>
                                        {{ $row['file_count'] ?? $row['available'] ?? '—' }}
                                    </td>
                                    <td class="collective-sheet__sold-cell {{ !empty($row['is_complete']) ? 'is-done' : '' }}">
                                        {{ $row['sold_display'] ?? '—' }}
                                        @if(!empty($row['is_complete']))
                                            <span aria-hidden="true"> ✓</span>
                                        @endif
                                    </td>
                                    <td class="collective-sheet__bal-cell">
                                        {{ $row['file_bal_display'] ?? '—' }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif

            <div class="collective-sheet__next">
                <div class="collective-sheet__next-title">Next deal</div>
                <div class="collective-sheet__next-box">
                    <span class="collective-sheet__next-land">{{ $collective['remaining_land_sheet'] ?? '—' }}</span>
                    <span class="collective-sheet__next-note">Leftover land after sold payment files</span>
                </div>
            </div>
        </div>

        @if(($collective['files'] ?? []) !== [])
            <div class="small fw-semibold mb-1">Lines</div>
            <ul class="small mb-0 ps-3">
                @foreach($collective['files'] as $member)
                    <li>{{ $member['name'] }}</li>
                @endforeach
            </ul>
        @endif
    </div>
</div>
@endsection

@push('modals')
<div class="modal fade" id="collectiveExemptionModal" tabindex="-1" aria-labelledby="collectiveExemptionModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <form
            method="post"
            action="{{ route('sale.files.collectives.apply-exemption', [$project, $collective['id']]) }}"
            class="modal-content"
            id="collective-exemption-form"
        >
            @csrf
            <div class="modal-header">
                <div>
                    <h2 class="modal-title h5 mb-1" id="collectiveExemptionModalTitle">Choose exemption</h2>
                    <p class="small text-muted mb-0">Pick which formula to apply on {{ $landLabel }}.</p>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="exemption-pick-hint mb-0">
                    This updates the Files / Sold / File Bal numbers using the selected exemption.
                </p>
                <div class="exemption-pick-list mt-3" role="radiogroup" aria-label="Exemption options">
                    @forelse(($exemptionOptions ?? []) as $index => $option)
                        <label class="exemption-pick-option">
                            <input
                                type="radio"
                                name="snapshot_id"
                                value="{{ $option['id'] ?? '' }}"
                                @checked($index === 0)
                            >
                            <div class="exemption-pick-card">
                                <div class="exemption-pick-card__top">
                                    <h3 class="exemption-pick-card__title">{{ $option['title'] ?? 'Exemption' }}</h3>
                                    <span class="exemption-pick-card__badge {{ !empty($option['is_current']) ? 'is-live' : '' }}">
                                        {{ $option['badge'] ?? '' }}
                                    </span>
                                </div>
                                <div class="exemption-pick-card__summary">{{ $option['summary'] ?? '—' }}</div>
                                <div class="exemption-pick-card__meta">
                                    {{ $option['marla_label'] ?? '' }}
                                    @if(!empty($option['date_label']))
                                        · {{ $option['date_label'] }}
                                    @endif
                                </div>
                                @if(!empty($option['components']))
                                    <div class="exemption-pick-breakdown">
                                        @foreach($option['components'] as $component)
                                            <div class="exemption-pick-comp">
                                                <div class="exemption-pick-comp__head">
                                                    <span class="exemption-pick-comp__title">{{ $component['label'] ?? '' }}</span>
                                                    <span class="exemption-pick-comp__pool">Pool {{ $component['percent'] ?? '' }}</span>
                                                </div>
                                                @if(!empty($component['plots']))
                                                    <ul class="exemption-pick-comp__plots">
                                                        @foreach($component['plots'] as $plot)
                                                            <li>
                                                                <span>{{ $plot['label'] ?? 'Plot' }}</span>
                                                                <span>
                                                                    <strong>{{ $plot['marla_label'] ?? '—' }}</strong>
                                                                    @if(!empty($plot['share_label']) && ($plot['share_percent'] ?? 0) > 0)
                                                                        · share {{ $plot['share_label'] }}
                                                                    @endif
                                                                </span>
                                                            </li>
                                                        @endforeach
                                                    </ul>
                                                @else
                                                    <div class="exemption-pick-comp__empty">No plot marla configured</div>
                                                @endif
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        </label>
                    @empty
                        <div class="exemption-pick-empty">
                            No exemption setups found yet.
                            @if($project->isDha())
                                Use <strong>Add exemption</strong> to create a project exemption, then it will apply here.
                            @else
                                Configure exemptions from the Sale exemption page first.
                            @endif
                        </div>
                    @endforelse
                </div>
            </div>
            <div class="modal-footer justify-content-between flex-wrap gap-2">
                <div>
                    @if($project->isDha())
                        <a
                            href="{{ route('sale.projects.exemption.edit', ['project' => $project, 'return_collective_id' => $collective['id']]) }}"
                            class="btn btn-outline-theme"
                        >
                            <i class="bi bi-plus-lg" aria-hidden="true"></i>
                            Add exemption
                        </a>
                    @endif
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <button type="button" class="btn btn-outline-theme" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-pink" @disabled(empty($exemptionOptions))>
                        Apply exemption
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

@php
    $activeExemption = $activeExemption ?? [
        'summary' => $collective['exemption_summary'] ?? '—',
        'marla_label' => '1 acre = '.rtrim(rtrim(number_format((float) ($collective['marla_per_acre'] ?? 160), 4, '.', ''), '0'), '.').'M',
        'components' => [],
        'has_details' => false,
    ];
@endphp
<div class="modal fade" id="activeExemptionViewModal" tabindex="-1" aria-labelledby="activeExemptionViewModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h2 class="modal-title h5 mb-1" id="activeExemptionViewModalTitle">Active exemption</h2>
                    <p class="small text-muted mb-0">Full formula currently applied on {{ $landLabel }}.</p>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" style="padding: 1.2rem 1.35rem; background: #f8fafc;">
                <div class="exemption-view-meta">
                    <span><strong>Summary:</strong> {{ $activeExemption['summary'] ?? '—' }}</span>
                    <span><strong>{{ $activeExemption['marla_label'] ?? '' }}</strong></span>
                </div>

                @forelse(($activeExemption['components'] ?? []) as $component)
                    <div class="exemption-view-component">
                        <div class="exemption-view-component__head">
                            <h3 class="exemption-view-component__title">{{ $component['label'] ?? 'Component' }}</h3>
                            <span class="exemption-view-component__pct">Pool {{ $component['pool_percent_label'] ?? '' }}</span>
                        </div>
                        @if(!empty($component['plot_types']))
                            <div class="table-responsive">
                                <table class="exemption-view-plots">
                                    <thead>
                                        <tr>
                                            <th>Plot file</th>
                                            <th>Share %</th>
                                            <th>Marla / plot</th>
                                            <th>Nominal</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($component['plot_types'] as $plot)
                                            <tr>
                                                <td>{{ $plot['label'] ?? '—' }}</td>
                                                <td>{{ $plot['share_percent_label'] ?? '—' }}</td>
                                                <td>{{ $plot['marla_per_plot_label'] ?? '—' }}</td>
                                                <td>{{ $plot['nominal_marla_label'] ?? '—' }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <p class="small text-muted mb-0">No plot types in this component.</p>
                        @endif
                    </div>
                @empty
                    <div class="exemption-view-empty">
                        No exemption details stored on this sale file yet. Choose or add an exemption first.
                    </div>
                @endforelse

                @if(!empty($activeFileCalculator['rows']))
                    <div class="mt-3">
                        <h3 class="h6 mb-2">Formula on this sale file</h3>
                        @include('sales.partials.exemption-file-calculator-table', ['fileCalculator' => $activeFileCalculator])
                    </div>
                @endif
            </div>
            <div class="modal-footer" style="border-top: 1px solid rgba(15, 23, 42, 0.08); background: #fff;">
                <button type="button" class="btn btn-outline-theme" data-bs-dismiss="modal">Close</button>
                <button
                    type="button"
                    class="btn btn-pink"
                    data-bs-dismiss="modal"
                    data-bs-toggle="modal"
                    data-bs-target="#collectiveExemptionModal"
                >
                    Choose exemption
                </button>
            </div>
        </div>
    </div>
</div>
@endpush
