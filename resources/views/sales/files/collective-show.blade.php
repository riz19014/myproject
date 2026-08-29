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
</style>
@endpush

@section('content')
@php
    $balanceRows = $collective['balance_rows'] ?? ($collective['formula_columns'] ?? []);
    $landLabel = $collective['total_land_sheet'] ?? ($collective['total_land_area'] ?? '—');
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
        <div class="small text-muted mb-3">
            Formula: {{ $collective['exemption_summary'] }}
            · 1 acre = {{ rtrim(rtrim(number_format((float) ($collective['marla_per_acre'] ?? 160), 4, '.', ''), '0'), '.') }}M
        </div>

        <form method="post" action="{{ route('sale.files.collectives.apply-exemption', [$project, $collective['id']]) }}"
              class="row g-2 align-items-end mb-4">
            @csrf
            <div class="col-sm-8 col-md-6">
                <label for="apply-exemption-{{ $collective['id'] }}" class="form-label small mb-1">Apply exemption again</label>
                <select name="snapshot_id" id="apply-exemption-{{ $collective['id'] }}" class="form-select form-select-sm">
                    <option value="">Current project exemption setup</option>
                    @foreach(($exemptionOptions ?? []) as $option)
                        <option value="{{ $option['id'] }}">{{ $option['label'] }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-sm-4 col-md-auto">
                <button type="submit" class="btn btn-sm btn-pink w-100">Apply</button>
            </div>
        </form>

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
