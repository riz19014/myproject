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
</style>
@endpush

@section('content')
@php
    $summary = $fileSaleSummary ?? ['totals' => [], 'daybook_rows' => [], 'moved_files' => [], 'files_land_columns' => []];
    $totals = $summary['totals'] ?? [];
    $daybookRows = $summary['daybook_rows'] ?? [];
    $movedFiles = $summary['moved_files'] ?? [];
    $filesLandColumns = $summary['files_land_columns'] ?? [];
@endphp

<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
    <div>
        <h1 class="mb-1">File Sale</h1>
        <p class="text-muted small mb-0">Project: <strong>{{ $project->name }}</strong></p>
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
            <h2 class="h6 mb-0">Sale land files — area balance</h2>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped table-theme mb-0 align-middle">
                    <thead>
                        <tr>
                            <th style="width: 56px;">#</th>
                            <th>File</th>
                            <th>Total land</th>
                            <th class="text-end">Sold</th>
                            <th class="text-end">Available</th>
                            <th>Status</th>
                            <th style="width: 120px;">Sell</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($movedFiles as $file)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td class="fw-semibold">{{ $file['name'] ?? $file['label'] ?? '—' }}</td>
                                <td class="small">{{ $file['total_label'] ?? '—' }}</td>
                                <td class="text-end small">{{ $file['sold_label'] ?? '—' }}</td>
                                <td class="text-end small fw-semibold">{{ $file['remaining_label'] ?? '—' }}</td>
                                <td>
                                    @php
                                        $status = $file['status'] ?? 'Available';
                                    @endphp
                                    @if(($file['is_fully_sold'] ?? false) || $status === 'Fully Sold')
                                        <span class="badge text-bg-secondary">Fully Sold</span>
                                    @elseif($status === 'Partially Sold')
                                        <span class="badge text-bg-warning">Partially Sold</span>
                                    @else
                                        <span class="badge text-bg-success">Available</span>
                                    @endif
                                </td>
                                <td>
                                    @if($file['is_fully_sold'] ?? false)
                                        <span class="text-muted small">—</span>
                                    @else
                                        <a href="{{ route('daybook.index', ['project' => $project->id, 'purchase_file_id' => $file['id'] ?? null]) }}" class="btn btn-sm btn-pink">Daybook</a>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card card-theme mb-4">
        <div class="card-header py-3">
            <h2 class="h6 mb-0">Daybook</h2>
        </div>
        <div class="card-body p-0">
            @if($daybookRows === [])
                <p class="text-muted small mb-0 p-3">No daybook entries linked to these sale land files for this project.</p>
            @else
                <div class="table-responsive">
                    <table class="table table-striped table-theme mb-0 align-middle">
                        <thead>
                            <tr>
                                <th>Category</th>
                                <th>Sub Category</th>
                                <th class="text-end" style="width: 160px;">Total Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($daybookRows as $row)
                                <tr>
                                    <td>{{ $row['category'] }}</td>
                                    <td>{{ $row['sub_category'] }}</td>
                                    <td class="text-end fw-semibold">{{ $row['total_amount_formatted'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
@endif

@if($project->projectFiles->isNotEmpty())
    <div class="card card-theme">
        <div class="card-header py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h2 class="h6 mb-0">Project inventory files</h2>
            <a href="{{ route('sale.files.create', $project) }}" class="btn btn-sm btn-pink">Add file</a>
        </div>
        <div class="card-body p-0">
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
        </div>
    </div>
@endif

@if($movedFiles !== [])
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    var btn = document.getElementById('file-sale-files-count-btn');
    var content = document.getElementById('file-sale-files-popover-content');
    if (!btn || !content || typeof bootstrap === 'undefined' || !bootstrap.Popover) return;

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
});
</script>
@endpush
@endif
@endsection
