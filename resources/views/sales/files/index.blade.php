@extends('layouts.app')

@section('title', 'File Sale — '.$project->name)

@push('head')
<style>
    .file-sale-strip__files-label {
        flex: 0 0 auto;
        min-width: 110px;
        padding: 0.85rem 1rem;
        border-right: 2px solid #334155;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 0.45rem;
        text-align: center;
        background: #f8fafc;
    }
    .file-sale-strip__count-btn {
        border: 1px solid rgba(249, 115, 22, 0.28);
        background: rgba(249, 115, 22, 0.1);
        border-radius: 10px;
        padding: 0.4rem 0.75rem;
        cursor: pointer;
        line-height: 1.2;
        transition: background 0.15s ease, border-color 0.15s ease, box-shadow 0.15s ease;
    }
    .file-sale-strip__count-btn:hover,
    .file-sale-strip__count-btn:focus {
        background: rgba(249, 115, 22, 0.16);
        border-color: rgba(249, 115, 22, 0.45);
        box-shadow: 0 0 0 3px rgba(249, 115, 22, 0.12);
        outline: none;
    }
    .file-sale-strip__count-num {
        display: block;
        font-size: 1.35rem;
        font-weight: 700;
        color: var(--accent-orange, #f97316);
        line-height: 1.1;
    }
    .file-sale-strip__count-text {
        display: block;
        font-size: 0.68rem;
        font-weight: 600;
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        margin-top: 0.1rem;
    }
    .file-sale-strip__files-title {
        font-size: 0.95rem;
        font-weight: 700;
        color: #0f172a;
        line-height: 1.2;
    }
    .file-sale-popover {
        max-width: min(320px, calc(100vw - 2rem));
    }
    .file-sale-popover .popover-header {
        font-size: 0.85rem;
        font-weight: 700;
        background: #f8fafc;
        border-bottom: 1px solid #e2e8f0;
    }
    .file-sale-popover .popover-body {
        font-size: 0.875rem;
        color: #334155;
        padding: 0.65rem 0.85rem;
    }
    .file-sale-popover__list {
        margin: 0;
        padding-left: 1.1rem;
    }
    .file-sale-popover__list li + li {
        margin-top: 0.3rem;
    }
    .file-sale-strip__sold-land .file-sale-strip__total-land-value {
        color: #b45309;
    }
    .file-sale-strip__left-land .file-sale-strip__total-land-value {
        color: #047857;
    }
    .file-sale-leftover-table__left {
        color: #047857;
    }
    .leftover-land-balance__list {
        display: flex;
        flex-direction: column;
        gap: 0.65rem;
    }
    .leftover-land-balance__item {
        border: 1px solid rgba(15, 23, 42, 0.1);
        border-radius: 12px;
        overflow: hidden;
        background: #fff;
    }
    .leftover-land-balance__item--summary {
        border-color: rgba(15, 23, 42, 0.16);
        background: #f8fafc;
    }
    .leftover-land-balance__item--nested {
        background: #fff;
    }
    .leftover-land-balance__summary {
        width: 100%;
        border: 0;
        background: transparent;
        text-align: left;
        padding: 0.85rem 1rem;
        display: flex;
        align-items: flex-start;
        gap: 0.75rem;
        cursor: pointer;
        transition: background 0.15s ease;
    }
    .leftover-land-balance__summary--nested {
        align-items: center;
        flex-wrap: wrap;
        gap: 0.55rem 0.85rem;
    }
    .leftover-land-balance__summary:hover,
    .leftover-land-balance__summary:focus {
        background: rgba(248, 250, 252, 0.9);
        outline: none;
    }
    .leftover-land-balance__summary[aria-expanded="true"] {
        background: #fff;
        border-bottom: 1px solid rgba(15, 23, 42, 0.08);
    }
    .leftover-land-balance__summary-block {
        flex: 1 1 auto;
        min-width: 0;
    }
    .leftover-land-balance__summary-title {
        display: block;
        font-size: 0.72rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        color: #64748b;
        margin-bottom: 0.45rem;
    }
    .leftover-land-balance__summary-list {
        list-style: none;
        padding: 0;
        display: flex;
        flex-direction: column;
        gap: 0.35rem;
        font-size: 0.9rem;
        color: #334155;
    }
    .leftover-land-balance__summary-list strong {
        color: #0f172a;
        font-weight: 650;
    }
    .leftover-land-balance__summary:hover,
    .leftover-land-balance__summary:focus {
        background: rgba(248, 250, 252, 0.9);
        outline: none;
    }
    .leftover-land-balance__summary[aria-expanded="true"] {
        background: #f8fafc;
        border-bottom: 1px solid rgba(15, 23, 42, 0.08);
    }
    .leftover-land-balance__chevron {
        width: 0.55rem;
        height: 0.55rem;
        border-right: 2px solid #64748b;
        border-bottom: 2px solid #64748b;
        transform: rotate(-45deg);
        transition: transform 0.15s ease;
        flex: 0 0 auto;
        margin-top: -0.15rem;
    }
    .leftover-land-balance__summary[aria-expanded="true"] .leftover-land-balance__chevron {
        transform: rotate(45deg);
        margin-top: 0.1rem;
    }
    .leftover-land-balance__land {
        font-weight: 700;
        color: #047857;
    }
    .leftover-land-balance__meta {
        font-size: 0.84rem;
        color: #334155;
        flex: 1 1 auto;
        min-width: 0;
    }
    .leftover-land-balance__plots {
        display: flex;
        flex-wrap: wrap;
        gap: 0.35rem;
    }
    .leftover-land-balance__plots--summary {
        margin-top: 0.65rem;
    }
    .leftover-land-balance__plot-chip {
        display: inline-flex;
        align-items: center;
        padding: 0.18rem 0.5rem;
        border-radius: 999px;
        background: #eef2ff;
        color: #3730a3;
        font-size: 0.75rem;
        font-weight: 650;
        white-space: nowrap;
    }
    .leftover-land-balance__detail-inner {
        padding: 1rem;
    }
    .leftover-land-balance__stat {
        border: 1px solid rgba(15, 23, 42, 0.08);
        border-radius: 10px;
        padding: 0.7rem 0.85rem;
        background: #fff;
        height: 100%;
    }
    .leftover-land-balance__stat.is-sold {
        background: #fff7ed;
        border-color: rgba(234, 88, 12, 0.18);
    }
    .leftover-land-balance__stat.is-left {
        background: #ecfdf5;
        border-color: rgba(5, 150, 105, 0.18);
    }
    .leftover-land-balance__stat.is-total {
        background: #f8fafc;
    }
    .leftover-land-balance__stat-label {
        display: block;
        font-size: 0.7rem;
        font-weight: 650;
        text-transform: uppercase;
        letter-spacing: 0.03em;
        color: #64748b;
        margin-bottom: 0.2rem;
    }
    .leftover-land-balance__stat-value {
        display: block;
        font-size: 0.92rem;
        font-weight: 700;
        color: #0f172a;
    }
    .file-sale-strip {
        border: 2px solid #334155;
        border-radius: 12px;
        overflow: hidden;
        background: #fff;
    }
    .file-sale-strip__top {
        display: flex;
        flex-wrap: wrap;
        align-items: stretch;
        border-bottom: 2px solid #334155;
    }
    .file-sale-strip__columns {
        flex: 1 1 auto;
        display: flex;
        flex-wrap: wrap;
        min-width: 0;
    }
    .file-sale-strip__col {
        flex: 1 1 0;
        min-width: 72px;
        text-align: center;
        border-right: 1px solid #cbd5e1;
    }
    .file-sale-strip__col:last-child {
        border-right: 0;
    }
    .file-sale-strip__col-code {
        padding: 0.55rem 0.5rem;
        font-weight: 700;
        font-size: 0.95rem;
        border-bottom: 1px solid #cbd5e1;
        background: #f8fafc;
        color: #0f172a;
    }
    .file-sale-strip__col-type {
        padding: 0.5rem 0.5rem;
        font-weight: 600;
        font-size: 0.92rem;
        border-bottom: 1px solid #e2e8f0;
        color: #334155;
    }
    .file-sale-strip__col-count {
        padding: 0.65rem 0.5rem 0.75rem;
        font-weight: 700;
        font-size: 1rem;
        color: #0f172a;
    }
    .file-sale-strip__total-land {
        flex: 0 0 auto;
        min-width: 150px;
        padding: 0.85rem 1rem;
        border-left: 2px solid #334155;
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        text-align: center;
        background: #f8fafc;
    }
    .file-sale-strip__total-land-label {
        font-size: 0.8rem;
        font-weight: 600;
        color: #64748b;
        margin-bottom: 0.25rem;
    }
    .file-sale-strip__total-land-value {
        font-size: 1.05rem;
        font-weight: 700;
        color: #0f172a;
        line-height: 1.3;
        word-break: break-word;
    }
    .file-sale-strip__amounts {
        padding: 0.9rem 1.1rem 1rem;
        display: flex;
        flex-direction: column;
        gap: 0.55rem;
    }
    .file-sale-strip__amount-line {
        font-size: 1rem;
        color: #0f172a;
    }
    .file-sale-strip__amount-line strong {
        font-weight: 700;
    }
    @media (max-width: 767.98px) {
        .file-sale-strip__top {
            flex-direction: column;
        }
        .file-sale-strip__files-label,
        .file-sale-strip__total-land {
            border-right: 0;
            border-left: 0;
            border-bottom: 2px solid #334155;
        }
        .file-sale-strip__col {
            min-width: 25%;
        }
    }

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

    .saved-sale-file-link {
        display: inline-flex;
        align-items: center;
        gap: 0.55rem;
        padding: 0.7rem 1rem;
        border: 1.5px solid rgba(249, 115, 22, 0.35);
        border-radius: 12px;
        background: linear-gradient(180deg, #fff7ed 0%, #fff 100%);
        color: #9a3412;
        font-size: 1.05rem;
        font-weight: 800;
        text-decoration: none;
        transition: border-color 0.15s ease, box-shadow 0.15s ease, background 0.15s ease;
    }
    .saved-sale-file-link:hover,
    .saved-sale-file-link:focus {
        border-color: rgba(249, 115, 22, 0.65);
        box-shadow: 0 0 0 3px rgba(249, 115, 22, 0.12);
        color: #7c2d12;
        background: #ffedd5;
    }
    .saved-sale-file-link__meta {
        font-size: 0.75rem;
        font-weight: 650;
        color: #64748b;
    }
    .saved-sale-files-list {
        display: flex;
        flex-wrap: wrap;
        gap: 0.75rem;
    }
</style>
@endpush

@section('content')
@php
    $summary = $fileSaleSummary ?? ['totals' => [], 'moved_files' => [], 'files_land_columns' => [], 'leftover_balance' => [], 'collectives' => [], 'separate_files' => [], 'open_collectives' => []];
    $totals = $summary['totals'] ?? [];
    $movedFiles = $summary['moved_files'] ?? [];
    $filesLandColumns = $summary['files_land_columns'] ?? [];
    $leftover = $summary['leftover_balance'] ?? ['formula_columns' => [], 'files' => [], 'totals' => []];
    $leftoverColumns = $leftover['formula_columns'] ?? [];
    $leftoverFiles = $leftover['files'] ?? [];
    $leftoverTotals = $leftover['totals'] ?? [];
    $collectives = $summary['collectives'] ?? [];
    $separateFiles = $summary['separate_files'] ?? [];
    $openCollectives = $summary['open_collectives'] ?? [];
    $suggestedSaleFileName = 'Collective-'.(count($collectives) + 1);
@endphp

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
        <h1 class="mb-1">File Sale</h1>
        <p class="text-muted small mb-0">Project: <strong><x-project-name :project="$project" /></strong></p>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <a href="{{ route('sale.projects.exemption.edit', $project) }}" class="btn btn-outline-theme">Exemption setup</a>
        <a href="{{ route('projects.sale-land', $project) }}" class="btn btn-outline-theme">Sale land</a>
        <a href="{{ route('sale.files.create', $project) }}" class="btn btn-outline-theme">Add project file</a>
        <a href="{{ route('purchase.files.index', ['project' => $project->id]) }}" class="btn btn-outline-theme">Purchase files</a>
        <a href="{{ route('projects.show', $project) }}" class="btn btn-outline-theme">Back to project</a>
        <a href="{{ route('sale.index') }}" class="btn btn-outline-theme">Sale menu</a>
    </div>
</div>

@if($movedFiles === [])
    <div class="card card-theme mb-4">
        <div class="card-body">
            <p class="text-muted mb-0">
                No sale land files moved to file sale yet. Open
                <a href="{{ route('projects.sale-land', $project) }}">Sale land</a>,
                select files, and click <strong>Move to File Sale</strong>.
            </p>
        </div>
    </div>
@else
    <div class="card card-theme mb-4">
        @if($filesLandColumns !== [])
            <div class="card-header py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                <h2 class="h6 mb-0">Total land &amp; files</h2>
                <div class="d-flex flex-wrap gap-2">
                    @if($separateFiles !== [])
                        <button type="button" class="btn btn-sm btn-pink" data-bs-toggle="modal" data-bs-target="#save-sale-file-modal">
                            Save as sale file
                        </button>
                    @endif
                    <a href="{{ route('sale.files.original-formula.pdf', $project) }}" class="btn btn-sm btn-outline-theme">
                        Print PDF
                    </a>
                </div>
            </div>
            <div class="card-body pt-4">
                <div id="file-sale-files-popover-content" class="d-none">
                    <ul class="file-sale-popover__list">
                        @foreach($movedFiles as $file)
                            <li>{{ $file['name'] }}</li>
                        @endforeach
                    </ul>
                </div>

                <div class="file-sale-strip">
                    <div class="file-sale-strip__top">
                        <div class="file-sale-strip__files-label">
                            <button type="button"
                                    class="file-sale-strip__count-btn"
                                    id="file-sale-files-count-btn"
                                    aria-label="{{ count($movedFiles) }} sale land files — click to view names">
                                <span class="file-sale-strip__count-num">{{ count($movedFiles) }}</span>
                                <span class="file-sale-strip__count-text">{{ count($movedFiles) === 1 ? 'File' : 'Files' }}</span>
                            </button>
                        </div>
                        <div class="file-sale-strip__columns">
                            @foreach($filesLandColumns as $column)
                                <div class="file-sale-strip__col">
                                    <div class="file-sale-strip__col-code">{{ $column['column_code'] }}</div>
                                    <div class="file-sale-strip__col-type">{{ $column['short_label'] }}</div>
                                    <div class="file-sale-strip__col-count">{{ $column['file_count'] }}</div>
                                </div>
                            @endforeach
                        </div>
                        <div class="file-sale-strip__total-land">
                            <div class="file-sale-strip__total-land-label">Total Land</div>
                            <div class="file-sale-strip__total-land-value">{{ $totals['total_land_area'] ?? '—' }}</div>
                        </div>
                        <div class="file-sale-strip__total-land file-sale-strip__sold-land">
                            <div class="file-sale-strip__total-land-label">Sold</div>
                            <div class="file-sale-strip__total-land-value">{{ $totals['sold_land_area'] ?? '—' }}</div>
                        </div>
                        <div class="file-sale-strip__total-land file-sale-strip__left-land">
                            <div class="file-sale-strip__total-land-label">Leftover</div>
                            <div class="file-sale-strip__total-land-value">{{ $totals['remaining_land_area'] ?? '—' }}</div>
                        </div>
                    </div>
                    <div class="file-sale-strip__amounts">
                        <div class="file-sale-strip__amount-line">
                            Total Amount Land = <strong>{{ $totals['grand_total_amount_formatted'] ?? '—' }}</strong>
                        </div>
                        <div class="file-sale-strip__amount-line">
                            Sale Files Amount = <strong>{{ $totals['sale_files_amount_formatted'] ?? '—' }}</strong>
                        </div>
                    </div>
                </div>
            </div>
        @else
            <div class="card-body">
                <p class="text-muted small mb-0">No land area found on the moved sale land files.</p>
            </div>
        @endif
    </div>

    <div class="card card-theme mb-4">
        <div class="card-header py-3">
            <h2 class="h6 mb-0">Saved sale files</h2>
            <p class="text-muted small mb-0">Click land to open Files / Sold / File Bal listing.</p>
        </div>
        <div class="card-body">
            @if($collectives === [])
                <p class="text-muted small mb-0">
                    No saved sale files yet.
                    @if($separateFiles !== [])
                        Click <strong>Save as sale file</strong> above to name this collective group and store header + lines.
                    @else
                        Move files from Sale land first.
                    @endif
                </p>
            @else
                <div class="saved-sale-files-list">
                    @foreach($collectives as $collective)
                        <a
                            href="{{ route('sale.files.collectives.show', [$project, $collective['id']]) }}"
                            class="saved-sale-file-link"
                            title="Open {{ $collective['name'] }}"
                        >
                            <span>{{ $collective['total_land_sheet'] ?? $collective['total_land_area'] }}</span>
                            <span class="saved-sale-file-link__meta">
                                {{ $collective['file_count'] }} {{ $collective['file_count'] === 1 ? 'file' : 'files' }}
                                · {{ $collective['is_open'] ? 'Open' : 'Completed' }}
                            </span>
                        </a>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    @if($separateFiles !== [])
        <div class="card card-theme mb-4">
            <div class="card-header py-3">
                <h2 class="h6 mb-0">Separate files (not yet saved)</h2>
                <p class="text-muted small mb-0">Select files and save as a named sale file, or add into an open one.</p>
            </div>
            <div class="card-body">
                <form method="post" action="{{ route('sale.files.collectives.group', $project) }}" id="file-sale-group-form">
                    @csrf
                    <div class="row g-3">
                        <div class="col-lg-7">
                            <div class="d-flex flex-wrap gap-2 mb-2">
                                <button type="button" class="btn btn-sm btn-outline-secondary" id="file-sale-group-check-all">Select all</button>
                                <button type="button" class="btn btn-sm btn-outline-secondary" id="file-sale-group-check-none">Clear</button>
                            </div>
                            <div class="border rounded-3 p-2" style="max-height: 220px; overflow: auto;">
                                @foreach($separateFiles as $file)
                                    <label class="d-flex align-items-center gap-2 py-1 px-1 mb-0">
                                        <input type="checkbox" class="form-check-input file-sale-group-check" name="sale_land_ids[]" value="{{ $file['id'] }}">
                                        <span>{{ $file['name'] }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                        <div class="col-lg-5">
                            <div class="mb-3">
                                <label class="form-label">Placement</label>
                                <div class="d-flex flex-column gap-2">
                                    <label class="mb-0">
                                        <input type="radio" name="placement" value="new_collective" class="form-check-input me-1" checked>
                                        New sale file
                                    </label>
                                    <label class="mb-0 {{ $openCollectives === [] ? 'opacity-50' : '' }}">
                                        <input type="radio" name="placement" value="existing_collective" class="form-check-input me-1"
                                               @disabled($openCollectives === [])>
                                        Existing open sale file
                                    </label>
                                </div>
                            </div>
                            <div class="mb-3" id="file-sale-group-name-wrap">
                                <label for="file-sale-group-name" class="form-label">Sale file name</label>
                                <input type="text" name="name" id="file-sale-group-name" class="form-control form-control-theme"
                                       value="{{ old('name', $suggestedSaleFileName) }}" maxlength="150">
                            </div>
                            @if($openCollectives !== [])
                                <div class="mb-3">
                                    <label for="file-sale-group-collective-id" class="form-label">Open sale file</label>
                                    <select name="collective_id" id="file-sale-group-collective-id" class="form-select">
                                        @foreach($openCollectives as $collective)
                                            <option value="{{ $collective['id'] }}">
                                                {{ $collective['name'] }} ({{ $collective['file_count'] }} file{{ $collective['file_count'] === 1 ? '' : 's' }})
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            @endif
                            <button type="submit" class="btn btn-pink">Save selected</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    @endif

    <div class="card card-theme mb-4">
        <div class="card-header py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
                <h2 class="h6 mb-0">Leftover land balance</h2>
                <p class="text-muted small mb-0">Remaining after daybook / sale-land sales (plot counts left).</p>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <a href="{{ route('sale.files.leftover-land.pdf', $project) }}" class="btn btn-sm btn-outline-theme">Print PDF</a>
                <a href="{{ route('sale-land.index') }}" class="btn btn-sm btn-outline-theme">Sold land files</a>
            </div>
        </div>
        <div class="card-body">
            @include('sales.partials.leftover-land-balance', [
                'leftoverColumns' => $leftoverColumns,
                'leftoverFiles' => $leftoverFiles,
                'leftoverTotals' => $leftoverTotals,
            ])
        </div>
    </div>
@endif

@if($separateFiles !== [])
@push('modals')
<div class="modal fade" id="save-sale-file-modal" tabindex="-1" aria-labelledby="save-sale-file-modal-title" aria-hidden="true">
    <div class="modal-dialog">
        <form method="post" action="{{ route('sale.files.collectives.store', $project) }}" class="modal-content card-theme">
            @csrf
            <div class="modal-header">
                <h2 class="modal-title h5 mb-0" id="save-sale-file-modal-title">Save as sale file</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="small text-muted mb-3">
                    Saves <strong>{{ count($separateFiles) }}</strong> separate file(s) as header + lines, and stores the current exemption formula scenario on the header.
                </p>
                <div class="mb-3">
                    <label for="save-sale-file-name" class="form-label">Sale file name</label>
                    <input type="text" name="name" id="save-sale-file-name" class="form-control form-control-theme"
                           value="{{ old('name', $suggestedSaleFileName) }}" required maxlength="150">
                </div>
                @foreach($separateFiles as $file)
                    <input type="hidden" name="sale_land_ids[]" value="{{ $file['id'] }}">
                @endforeach
                <div class="small text-muted">
                    <div class="fw-semibold text-body mb-1">Lines that will be saved</div>
                    <ul class="mb-0 ps-3">
                        @foreach($separateFiles as $file)
                            <li>{{ $file['name'] }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-pink">Save sale file</button>
            </div>
        </form>
    </div>
</div>
@endpush
@endif

@if($movedFiles !== [])
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    var btn = document.getElementById('file-sale-files-count-btn');
    var content = document.getElementById('file-sale-files-popover-content');
    if (btn && content && typeof bootstrap !== 'undefined' && bootstrap.Popover) {
        var popover = new bootstrap.Popover(btn, {
            title: 'Sale land files',
            content: content.innerHTML,
            html: true,
            trigger: 'click',
            placement: 'bottom',
            customClass: 'file-sale-popover',
            sanitize: false
        });

        document.addEventListener('click', function (e) {
            var pop = document.querySelector('.file-sale-popover');
            if (!btn.contains(e.target) && !(pop && pop.contains(e.target))) {
                popover.hide();
            }
        });
    }

    var checkAll = document.getElementById('file-sale-group-check-all');
    var checkNone = document.getElementById('file-sale-group-check-none');
    var groupForm = document.getElementById('file-sale-group-form');
    if (checkAll) {
        checkAll.addEventListener('click', function () {
            document.querySelectorAll('.file-sale-group-check').forEach(function (cb) { cb.checked = true; });
        });
    }
    if (checkNone) {
        checkNone.addEventListener('click', function () {
            document.querySelectorAll('.file-sale-group-check').forEach(function (cb) { cb.checked = false; });
        });
    }
    if (groupForm) {
        groupForm.addEventListener('submit', function (e) {
            var selected = document.querySelectorAll('.file-sale-group-check:checked');
            if (!selected.length) {
                e.preventDefault();
                alert('Select at least one separate file.');
                return;
            }
            var placement = groupForm.querySelector('input[name="placement"]:checked');
            if (placement && placement.value === 'existing_collective') {
                var select = document.getElementById('file-sale-group-collective-id');
                if (!select || !select.value) {
                    e.preventDefault();
                    alert('Select an open sale file.');
                }
            }
            if (placement && placement.value === 'new_collective') {
                var nameInput = document.getElementById('file-sale-group-name');
                if (!nameInput || !String(nameInput.value || '').trim()) {
                    e.preventDefault();
                    alert('Enter a sale file name.');
                }
            }
        });
    }
});
</script>
@endpush
@endif
@endsection
