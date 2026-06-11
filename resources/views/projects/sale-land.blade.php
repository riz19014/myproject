@extends('layouts.app')

@section('title', 'Sale land — '.$project->name)

@section('content')
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
    <div>
        <h1 class="mb-1">Sale land</h1>
        <p class="text-muted small mb-0">
            Project: <strong>{{ $project->name }}</strong>
            · Formula columns from <a href="{{ route('sale.projects.exemption.edit', $project) }}">project exemption setup</a>
        </p>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <a href="{{ route('purchase.files.index', ['project' => $project->id]) }}" class="btn btn-outline-theme btn-sm">Purchase files</a>
        <a href="{{ route('projects.show', $project) }}" class="btn btn-outline-theme">Back to project</a>
        <a href="{{ route('sale.projects.exemption.edit', $project) }}" class="btn btn-outline-theme btn-sm">Exemption setup</a>
        <a href="{{ route('sale.index') }}" class="btn btn-outline-theme">Sale menu</a>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success small">{{ session('success') }}</div>
@endif

@php
    $formulaColumns = $saleLandSheet['formula_columns'] ?? [];
    $sheetRows = $saleLandSheet['rows'] ?? [];
    $formulaTotals = $saleLandSheet['formula_totals'] ?? ['total_land' => '—', 'formula_values' => []];
@endphp

@if($sheetRows === [])
    <div class="card card-theme mb-4">
        <div class="card-body">
            <p class="text-muted small mb-0">
                No sale land records yet. Open <a href="{{ route('purchase.files.index', ['project' => $project->id]) }}">purchase files</a>,
                click <strong>Sale land</strong> on a file, and confirm to generate formula files here.
            </p>
        </div>
    </div>
@else
    <div class="card card-theme mb-4">
        <div class="card-body p-0">
            <div class="sale-land-sheet-split">
                <div class="sale-land-sheet-split__frozen">
                    <table class="table table-sm table-bordered table-striped table-theme mb-0 align-middle sale-land-sheet sale-land-sheet--frozen">
                        <thead>
                            <tr>
                                <th class="sale-land-sheet__file-col">File name</th>
                                <th class="sale-land-sheet__sr-col">SR</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($sheetRows as $row)
                                <tr data-row-idx="{{ $loop->index }}">
                                    @if($row['show_file_name'])
                                        <td class="fw-semibold small sale-land-sheet__file-name-cell" rowspan="{{ $row['file_name_rowspan'] }}">
                                            {{ $row['file_name'] }}
                                        </td>
                                    @endif
                                    <td class="text-muted sale-land-sheet__sr-col">{{ $row['sr'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr class="sale-land-sheet__totals-row">
                                <td colspan="2" class="fw-semibold small">Total</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                <div class="sale-land-sheet-split__scroll">
                    <table class="table table-sm table-bordered table-striped table-theme mb-0 align-middle sale-land-sheet sale-land-sheet--scroll">
                        <thead>
                            <tr>
                                <th>LP</th>
                                <th>Land owner</th>
                                <th>Transfer to</th>
                                <th>Mouza</th>
                                <th>Khasra</th>
                                <th class="sale-land-sheet__land-col">Total land</th>
                                @foreach($formulaColumns as $column)
                                    <th class="text-end sale-land-sheet__formula-col {{ $loop->first ? 'sale-land-sheet__formula-block-start' : '' }}" title="{{ $column['plot_label'] }} — {{ $column['component_label'] }}">
                                        <span class="d-block fw-semibold sale-land-sheet__formula-label">{{ $column['short_label'] }}</span>
                                        <span class="d-block sale-land-sheet__formula-code">{{ $column['code'] }}</span>
                                    </th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($sheetRows as $row)
                                <tr data-row-idx="{{ $loop->index }}"
                                    data-purchase-file-id="{{ $row['purchase_file_id'] }}"
                                    data-moza-key="{{ $row['moza_key'] }}">
                                    <td class="small sale-land-sheet__editable"
                                        data-field="land_provider"
                                        data-value="{{ $row['land_provider'] }}"
                                        title="Double-click to edit">
                                        <span class="sale-land-sheet__editable-display">{{ $row['land_provider'] }}</span>
                                    </td>
                                    <td class="small">{{ $row['land_owner'] }}</td>
                                    <td class="small sale-land-sheet__editable"
                                        data-field="transfer_to"
                                        data-value="{{ $row['transfer_to'] }}"
                                        title="Double-click to edit">
                                        <span class="sale-land-sheet__editable-display">{{ $row['transfer_to'] }}</span>
                                    </td>
                                    <td class="small fw-semibold">{{ $row['moza'] }}</td>
                                    <td class="small">{{ $row['khasra'] }}</td>
                                    <td class="small sale-land-sheet__land-col fw-semibold text-orange">{{ $row['total_land'] }}</td>
                                    @foreach($formulaColumns as $column)
                                        @php
                                            $formula = $row['formula_values'][$column['plot_key']] ?? null;
                                        @endphp
                                        <td class="text-end small sale-land-sheet__formula-col {{ $loop->first ? 'sale-land-sheet__formula-block-start' : '' }}">
                                            @if($formula)
                                                <span class="fw-semibold sale-land-sheet__formula-value">{{ $formula['display'] }}</span>
                                                @if(($formula['breakdown'] ?? '—') !== '—')
                                                    <div class="sale-land-sheet__formula-breakdown">{{ $formula['breakdown'] }}</div>
                                                @endif
                                            @else
                                                <span class="text-muted">—</span>
                                            @endif
                                        </td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr class="sale-land-sheet__totals-row">
                                <td colspan="5" class="fw-semibold small">Total</td>
                                <td class="small sale-land-sheet__land-col fw-semibold text-orange">{{ $formulaTotals['total_land'] }}</td>
                                @foreach($formulaColumns as $column)
                                    @php
                                        $formula = $formulaTotals['formula_values'][$column['plot_key']] ?? null;
                                    @endphp
                                    <td class="text-end small sale-land-sheet__formula-col {{ $loop->first ? 'sale-land-sheet__formula-block-start' : '' }}">
                                        @if($formula)
                                            <span class="fw-semibold sale-land-sheet__formula-value">{{ $formula['display'] }}</span>
                                            @if(($formula['breakdown'] ?? '—') !== '—')
                                                <div class="sale-land-sheet__formula-breakdown">{{ $formula['breakdown'] }}</div>
                                            @endif
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                @endforeach
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <p class="text-muted small mb-4">
        One row per <strong>Mouza</strong> per purchase file. Double-click <strong>LP</strong> or <strong>Transfer to</strong> to edit; press <kbd>Enter</kbd> to save. <strong>Land owner</strong> comes from the purchase file.
        Formula file counts use marla per acre
        <strong>{{ rtrim(rtrim(number_format($marlaPerAcreLand, 4, '.', ''), '0'), '.') }}</strong>
        from exemption setup.
    </p>
@endif

<div class="card card-theme">
    <div class="card-body">
        <h2 class="h6 mb-3">Land sales on this project</h2>
        @if($sales->isEmpty())
            <p class="text-muted small mb-0">No project-level sale land records yet. Use <strong>Files sale</strong> for plot sales on sale files.</p>
        @else
            <div class="table-responsive">
                <table class="table table-striped table-theme mb-0 align-middle">
                    <thead>
                        <tr>
                            <th style="width: 72px;">ID</th>
                            <th style="min-width: 200px;">Sale land</th>
                            <th class="text-end" style="width: 120px;">Cuttings</th>
                            <th class="text-end" style="width: 120px;">Net saleable</th>
                            <th>Parties / buyers</th>
                            <th class="text-end" style="width: 120px;">Total (Rs)</th>
                            <th style="width: 100px;">Date</th>
                            <th class="text-center" style="width: 56px;"><span class="visually-hidden">Cuttings</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($sales as $sale)
                            @php
                                $names = $sale->participants->map(function ($sp) {
                                    return $sp->party?->name ?? $sp->customer?->name ?? '—';
                                })->filter()->values();
                                $cutMarla = (float) $sale->landCuttings->sum('land_area_marla');
                                $netMarla = (float) $sale->land_area_marla - $cutMarla;
                            @endphp
                            <tr>
                                <td>{{ $sale->id }}</td>
                                <td class="small">{{ \App\Support\LandMeasure::formatAkmsLabelFromMarla((float) $sale->land_area_marla) }}</td>
                                <td class="text-end small">{{ $cutMarla > 0 ? \App\Support\LandMeasure::formatAkmsLabelFromMarla($cutMarla) : '—' }}</td>
                                <td class="text-end small fw-semibold {{ $netMarla < 0 ? 'text-danger' : '' }}">
                                    @if($netMarla < 0)
                                        −{{ number_format(abs($netMarla), 2) }} marla
                                    @else
                                        {{ \App\Support\LandMeasure::formatAkmsLabelFromMarla($netMarla) }}
                                    @endif
                                </td>
                                <td class="small">{{ $names->isEmpty() ? '—' : $names->implode(', ') }}</td>
                                <td class="text-end fw-semibold">{{ number_format((float) $sale->total_amount, 0) }}</td>
                                <td class="text-muted small">{{ $sale->created_at?->format('Y-m-d') }}</td>
                                <td class="text-center">
                                    <a href="{{ route('sale.records.land-cuttings.index', $sale) }}" class="btn btn-sm btn-outline-theme p-2" title="Land cutting" aria-label="Land cutting for sale {{ $sale->id }}">
                                        <i class="bi bi-scissors" aria-hidden="true"></i>
                                    </a>
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
    .sale-land-sheet-split {
        display: flex;
        align-items: flex-start;
        width: 100%;
    }
    .sale-land-sheet-split__frozen {
        flex: 0 0 auto;
        z-index: 2;
        background: #fff;
        box-shadow: 4px 0 12px -4px rgba(15, 23, 42, 0.18);
    }
    .sale-land-sheet-split__scroll {
        flex: 1 1 auto;
        min-width: 0;
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }
    .sale-land-sheet.table-theme {
        --sale-land-sr-width: 42px;
        --sale-land-file-width: 130px;
    }
    .sale-land-sheet thead th {
        font-size: 0.72rem;
        text-transform: uppercase;
        letter-spacing: 0.02em;
        color: #64748b;
        white-space: nowrap;
        vertical-align: bottom;
    }
    .sale-land-sheet--frozen.table-theme th,
    .sale-land-sheet--frozen.table-theme td {
        background: #fff !important;
        background-color: #fff !important;
    }
    .sale-land-sheet--frozen.table-theme.table-striped tbody tr:nth-of-type(odd) td {
        background: #f7f8f9 !important;
        background-color: #f7f8f9 !important;
    }
    .sale-land-sheet--frozen.table-theme tbody tr:hover td {
        background: #f1f3f5 !important;
        background-color: #f1f3f5 !important;
    }
    .sale-land-sheet--frozen.table-theme td.sale-land-sheet__file-name-cell,
    .sale-land-sheet--frozen.table-theme th.sale-land-sheet__file-col {
        background: #fff7ed !important;
        background-color: #fff7ed !important;
    }
    .sale-land-sheet--frozen.table-theme tbody tr:hover td.sale-land-sheet__file-name-cell {
        background: #fff7ed !important;
        background-color: #fff7ed !important;
    }
    .sale-land-sheet--frozen.table-theme thead th {
        background: #f1f5f9 !important;
        background-color: #f1f5f9 !important;
    }
    .sale-land-sheet--frozen.table-theme th.sale-land-sheet__file-col {
        background: #f1f5f9 !important;
        background-color: #f1f5f9 !important;
    }
    .sale-land-sheet__sr-col {
        width: var(--sale-land-sr-width);
        min-width: var(--sale-land-sr-width);
        max-width: var(--sale-land-sr-width);
    }
    .sale-land-sheet__file-col {
        width: var(--sale-land-file-width);
        min-width: var(--sale-land-file-width);
        max-width: var(--sale-land-file-width);
    }
    .sale-land-sheet__file-name-cell {
        vertical-align: middle;
    }
    .sale-land-sheet--frozen.table-theme td.sale-land-sheet__sr-col,
    .sale-land-sheet--frozen.table-theme th.sale-land-sheet__sr-col {
        border-right: 2px solid rgba(249, 115, 22, 0.25) !important;
    }
    .sale-land-sheet--scroll.table-theme th:first-child,
    .sale-land-sheet--scroll.table-theme td:first-child {
        border-left: 0 !important;
    }
    .sale-land-sheet__formula-block-start {
        border-left: 2px solid rgba(249, 115, 22, 0.35) !important;
    }
    .sale-land-sheet--scroll.table-theme th.sale-land-sheet__formula-col,
    .sale-land-sheet--scroll.table-theme td.sale-land-sheet__formula-col {
        background: #fff7ed !important;
        background-color: #fff7ed !important;
    }
    .sale-land-sheet--scroll.table-theme.table-striped tbody tr:nth-of-type(odd) td.sale-land-sheet__formula-col {
        background: #ffedd5 !important;
        background-color: #ffedd5 !important;
    }
    .sale-land-sheet--scroll.table-theme tbody tr:hover td.sale-land-sheet__formula-col {
        background: #fed7aa !important;
        background-color: #fed7aa !important;
    }
    .sale-land-sheet--scroll.table-theme th.sale-land-sheet__formula-col {
        background: #fdba74 !important;
        background-color: #fdba74 !important;
        color: #7c2d12;
        vertical-align: bottom;
        padding-top: 0.5rem;
        padding-bottom: 0.5rem;
    }
    .sale-land-sheet__formula-label {
        font-size: 0.78rem;
        line-height: 1.2;
        color: #7c2d12;
    }
    .sale-land-sheet__formula-code {
        font-size: 0.72rem;
        font-weight: 600;
        color: #9a3412;
        letter-spacing: 0.03em;
        margin-top: 0.15rem;
    }
    .sale-land-sheet__formula-col {
        min-width: 88px;
    }
    .sale-land-sheet__formula-value {
        color: #c2410c;
    }
    .sale-land-sheet__formula-breakdown {
        font-size: 0.68rem;
        color: #9a3412;
        opacity: 0.85;
    }
    .sale-land-sheet__totals-row td {
        background: #f8fafc !important;
        background-color: #f8fafc !important;
        border-top: 2px solid rgba(249, 115, 22, 0.35) !important;
        vertical-align: middle;
    }
    .sale-land-sheet--frozen.table-theme tfoot td {
        background: #f8fafc !important;
        background-color: #f8fafc !important;
    }
    .sale-land-sheet--scroll.table-theme tfoot td.sale-land-sheet__formula-col {
        background: #ffedd5 !important;
        background-color: #ffedd5 !important;
    }
    .sale-land-sheet__land-col {
        white-space: nowrap;
        min-width: 130px;
    }
    .sale-land-sheet .text-orange {
        color: #f97316;
    }
    .sale-land-sheet__editable {
        cursor: text;
        min-width: 120px;
    }
    .sale-land-sheet__editable:hover {
        background: rgba(249, 115, 22, 0.06) !important;
    }
    .sale-land-sheet__editable.is-editing {
        padding: 0.2rem;
    }
    .sale-land-sheet__editable-input {
        width: 100%;
        min-width: 110px;
        font-size: 0.85rem;
        padding: 0.25rem 0.4rem;
        border: 1px solid rgba(249, 115, 22, 0.45);
        border-radius: 4px;
    }
    .sale-land-sheet__editable.is-saving {
        opacity: 0.6;
    }
</style>
@endpush

@if($sheetRows !== [])
@push('scripts')
<script>
(function() {
    function syncSaleLandRowHeights() {
        var frozenRows = document.querySelectorAll('.sale-land-sheet--frozen tbody tr');
        var scrollRows = document.querySelectorAll('.sale-land-sheet--scroll tbody tr');
        var frozenHead = document.querySelector('.sale-land-sheet--frozen thead tr');
        var scrollHead = document.querySelector('.sale-land-sheet--scroll thead tr');

        if (frozenHead && scrollHead) {
            frozenHead.style.height = '';
            scrollHead.style.height = '';
            var headH = Math.max(frozenHead.offsetHeight, scrollHead.offsetHeight);
            frozenHead.style.height = headH + 'px';
            scrollHead.style.height = headH + 'px';
        }

        frozenRows.forEach(function(fRow, i) {
            var sRow = scrollRows[i];
            if (!sRow) {
                return;
            }
            fRow.style.height = '';
            sRow.style.height = '';
            var rowH = Math.max(fRow.offsetHeight, sRow.offsetHeight);
            fRow.style.height = rowH + 'px';
            sRow.style.height = rowH + 'px';
        });

        var frozenFoot = document.querySelector('.sale-land-sheet--frozen tfoot tr');
        var scrollFoot = document.querySelector('.sale-land-sheet--scroll tfoot tr');
        if (frozenFoot && scrollFoot) {
            frozenFoot.style.height = '';
            scrollFoot.style.height = '';
            var footH = Math.max(frozenFoot.offsetHeight, scrollFoot.offsetHeight);
            frozenFoot.style.height = footH + 'px';
            scrollFoot.style.height = footH + 'px';
        }
    }

    syncSaleLandRowHeights();
    window.addEventListener('resize', syncSaleLandRowHeights);
    if (document.fonts && document.fonts.ready) {
        document.fonts.ready.then(syncSaleLandRowHeights);
    }

    var updateUrl = @json(route('projects.sale-land.moza-row.update', $project));
    var csrf = document.querySelector('meta[name="csrf-token"]');
    var token = csrf ? csrf.getAttribute('content') : '';
    var activeCell = null;

    function displayValue(value) {
        var v = String(value || '').trim();
        return v === '' ? '—' : v;
    }

    function closeEditor(cell, restore) {
        if (!cell || !cell.classList.contains('is-editing')) {
            return;
        }
        var input = cell.querySelector('.sale-land-sheet__editable-input');
        var previous = cell.dataset.value || '';
        var value = restore ? previous : (input ? input.value.trim() : previous);
        cell.classList.remove('is-editing');
        cell.innerHTML = '<span class="sale-land-sheet__editable-display">' + escapeHtml(displayValue(value)) + '</span>';
        cell.dataset.value = value;
        if (activeCell === cell) {
            activeCell = null;
        }
    }

    function escapeHtml(value) {
        return String(value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function saveCell(cell, input) {
        var row = cell.closest('tr');
        var field = cell.dataset.field;
        var value = input.value.trim();
        var purchaseFileId = row ? row.dataset.purchaseFileId : '';
        var mozaKey = row ? row.dataset.mozaKey : '';

        cell.classList.add('is-saving');

        fetch(updateUrl, {
            method: 'PATCH',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': token,
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({
                purchase_file_id: parseInt(purchaseFileId, 10),
                moza_key: mozaKey,
                field: field,
                value: value
            })
        })
        .then(function(res) {
            if (!res.ok) {
                throw new Error('Save failed');
            }
            return res.json();
        })
        .then(function(data) {
            cell.classList.remove('is-saving', 'is-editing');
            cell.dataset.value = value;
            cell.innerHTML = '<span class="sale-land-sheet__editable-display">' + escapeHtml(displayValue(data.value)) + '</span>';
            activeCell = null;
            syncSaleLandRowHeights();
        })
        .catch(function() {
            cell.classList.remove('is-saving');
            closeEditor(cell, true);
            alert('Could not save. Please try again.');
        });
    }

    function openEditor(cell) {
        if (activeCell && activeCell !== cell) {
            closeEditor(activeCell, true);
        }
        if (cell.classList.contains('is-editing')) {
            return;
        }

        var current = cell.dataset.value || '';
        cell.classList.add('is-editing');
        cell.innerHTML = '<input type="text" class="sale-land-sheet__editable-input form-control form-control-theme" value="' + escapeHtml(current === '—' ? '' : current) + '">';
        var input = cell.querySelector('.sale-land-sheet__editable-input');
        activeCell = cell;
        input.focus();
        input.select();

        input.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                saveCell(cell, input);
            } else if (e.key === 'Escape') {
                e.preventDefault();
                closeEditor(cell, true);
            }
        });

        input.addEventListener('blur', function() {
            setTimeout(function() {
                if (cell.classList.contains('is-editing') && !cell.classList.contains('is-saving')) {
                    closeEditor(cell, true);
                }
            }, 120);
        });
    }

    document.querySelectorAll('.sale-land-sheet__editable').forEach(function(cell) {
        cell.addEventListener('dblclick', function() {
            openEditor(cell);
        });
    });
})();
</script>
@endpush
@endif
