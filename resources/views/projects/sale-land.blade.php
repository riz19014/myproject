@extends('layouts.app')

@section('title', 'Sale land — '.$project->name)

@section('content')
@php
    $formulaColumns = $saleLandSheet['formula_columns'] ?? [];
    $sheetRows = $saleLandSheet['rows'] ?? [];
    $formulaTotals = $saleLandSheet['formula_totals'] ?? ['total_land' => '—', 'formula_values' => []];
    $scopedPurchaseFiles = $scopedPurchaseFiles ?? collect();
    $scopedPurchaseFileIds = $scopedPurchaseFiles->pluck('id')->all();
    $fileCount = collect($sheetRows)->where('show_file_name', true)->count();
    $rowCount = count($sheetRows);
@endphp
<div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-3">
    <div>
        <h1 class="mb-1">Sale land</h1>
        <p class="text-muted small mb-1">
            Project: <strong>{{ $project->name }}</strong>
            @if($scopedPurchaseFiles->isNotEmpty())
                · File: <strong>{{ $scopedPurchaseFiles->pluck('file_name')->implode(', ') }}</strong>
                · <a href="{{ route('projects.sale-land', $project) }}">View all</a>
            @endif
        </p>
        @if($sheetRows !== [])
            <p class="text-muted small mb-0">
                {{ $fileCount }} {{ $fileCount === 1 ? 'file' : 'files' }}
                · {{ $rowCount }} mouza {{ $rowCount === 1 ? 'row' : 'rows' }}
                · Total land: <strong>{{ $formulaTotals['total_land'] }}</strong>
            </p>
        @endif
    </div>
    <div class="d-flex flex-wrap gap-2 align-items-center justify-content-end">
        @if($sheetRows !== [])
            <a href="#" class="btn btn-outline-theme btn-sm sale-land-pdf-link" id="sale-land-pdf-btn" data-base-url="{{ route('projects.sale-land.pdf', $project) }}">Download PDF</a>
        @endif
        <a href="{{ route('purchase.files.index', ['project' => $project->id]) }}" class="btn btn-outline-theme btn-sm">Purchase files</a>
        <a href="{{ route('sale.files.index', $project) }}" class="btn btn-outline-theme btn-sm">Sale files</a>
        <a href="{{ route('projects.show', $project) }}" class="btn btn-outline-theme btn-sm">Back to project</a>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success small">{{ session('success') }}</div>
@endif

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
        <div class="card-body border-bottom py-3">
            <div class="row g-2 align-items-end">
                <div class="col-md-5">
                    <label for="sale-land-search" class="form-label small mb-1">Search</label>
                    <input type="search"
                           id="sale-land-search"
                           class="form-control form-control-theme form-control-sm"
                           placeholder="File, LP, owner, mouza, khasra…"
                           autocomplete="off">
                </div>
                <div class="col-md-7 d-flex flex-wrap align-items-center justify-content-md-end gap-2">
                    <span class="small text-muted" id="sale-land-search-count"></span>
                    <button type="button" class="btn btn-sm btn-outline-secondary" id="sale-land-check-all">Select all</button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" id="sale-land-check-none">Clear</button>
                    <a href="{{ route('sale.projects.exemption.edit', $project) }}" class="btn btn-sm btn-outline-secondary">Exemption setup</a>
                </div>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="sale-land-sheet-split" id="sale-land-sheet-split">
                <div class="sale-land-sheet-split__frozen">
                    <table class="table table-sm table-bordered table-theme mb-0 align-middle sale-land-sheet sale-land-sheet--frozen">
                        <thead>
                            <tr>
                                <th class="sale-land-sheet__file-col">File name</th>
                                <th class="sale-land-sheet__sr-col text-center">SR</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($sheetRows as $row)
                                <tr data-row-idx="{{ $loop->index }}" class="{{ $row['show_file_name'] ? 'sale-land-sheet__file-group-start' : '' }}">
                                    @if($row['show_file_name'])
                                        <td class="sale-land-sheet__file-name-cell" rowspan="{{ $row['file_name_rowspan'] }}">
                                            <div class="sale-land-sheet__file-name-wrap">
                                                <input type="checkbox"
                                                       class="form-check-input sale-land-file-check"
                                                       value="{{ $row['purchase_file_id'] }}"
                                                       id="sale-land-file-{{ $row['purchase_file_id'] }}"
                                                       aria-label="Include {{ $row['file_name'] }} in PDF"
                                                       @checked(in_array($row['purchase_file_id'], $scopedPurchaseFileIds, true))>
                                                <label class="sale-land-sheet__file-name-text mb-0" for="sale-land-file-{{ $row['purchase_file_id'] }}">{{ $row['file_name'] }}</label>
                                                <form method="post"
                                                      action="{{ route('projects.sale-land.destroy', [$project, $row['purchase_file_id']]) }}"
                                                      class="sale-land-sheet__file-delete-form">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="button"
                                                            class="btn btn-sm btn-outline-danger sale-land-sheet__file-delete-btn btn-delete-confirm"
                                                            data-title="Delete sale land?"
                                                            data-text="Remove &quot;{{ $row['file_name'] }}&quot; from sale land? The purchase file and sellers will stay; only this sale land record and its formula overrides will be removed."
                                                            data-confirm="Yes, delete"
                                                            title="Delete sale land"
                                                            aria-label="Delete sale land for {{ $row['file_name'] }}">
                                                        <i class="bi bi-trash" aria-hidden="true"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    @endif
                                    <td class="sale-land-sheet__sr-col text-center">{{ $row['sr'] }}</td>
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
                    <table class="table table-sm table-bordered table-theme mb-0 align-middle sale-land-sheet sale-land-sheet--scroll">
                        <thead>
                            <tr>
                                <th>LP</th>
                                <th>Land owner</th>
                                <th>Transfer to</th>
                                <th>Mouza</th>
                                <th>Khasra</th>
                                <th class="sale-land-sheet__land-col">Total land</th>
                                @foreach($formulaColumns as $column)
                                    <th class="text-end sale-land-sheet__formula-col" title="{{ $column['plot_label'] }} — {{ $column['component_label'] }}">
                                        {{ $column['short_label'] }}<br><span class="sale-land-sheet__formula-code">{{ $column['code'] }}</span>
                                    </th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($sheetRows as $row)
                                <tr data-row-idx="{{ $loop->index }}"
                                    class="{{ $row['show_file_name'] ? 'sale-land-sheet__file-group-start' : '' }}"
                                    data-purchase-file-id="{{ $row['purchase_file_id'] }}"
                                    data-moza-key="{{ $row['moza_key'] }}"
                                    data-search-text="{{ e(strtolower(implode(' ', array_filter([
                                        $row['file_name'],
                                        $row['land_provider'],
                                        $row['land_owner'],
                                        $row['transfer_to'],
                                        $row['moza'],
                                        $row['khasra'],
                                        $row['total_land'],
                                        collect($row['formula_values'] ?? [])->pluck('display')->implode(' '),
                                        collect($row['formula_values'] ?? [])->pluck('breakdown')->implode(' '),
                                    ])))) }}">
                                    <td class="sale-land-sheet__editable"
                                        data-field="land_provider"
                                        data-value="{{ $row['land_provider'] }}"
                                        title="Double-click to edit LP">
                                        <span class="sale-land-sheet__editable-display">{{ $row['land_provider'] }}</span>
                                    </td>
                                    <td>{{ $row['land_owner'] }}</td>
                                    <td class="sale-land-sheet__editable"
                                        data-field="transfer_to"
                                        data-value="{{ $row['transfer_to'] }}"
                                        title="Double-click to edit transfer to">
                                        <span class="sale-land-sheet__editable-display">{{ $row['transfer_to'] }}</span>
                                    </td>
                                    <td>{{ $row['moza'] }}</td>
                                    <td>{{ $row['khasra'] }}</td>
                                    <td class="sale-land-sheet__land-col fw-semibold">{{ $row['total_land'] }}</td>
                                    @foreach($formulaColumns as $column)
                                        @php
                                            $formula = $row['formula_values'][$column['plot_key']] ?? null;
                                        @endphp
                                        <td class="text-end sale-land-sheet__formula-col">
                                            @if($formula)
                                                {{ $formula['display'] }}
                                                @if(($formula['breakdown'] ?? '—') !== '—')
                                                    <div class="sale-land-sheet__formula-breakdown">{{ $formula['breakdown'] }}</div>
                                                @endif
                                            @else
                                                —
                                            @endif
                                        </td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr class="sale-land-sheet__totals-row">
                                <td colspan="5" class="fw-semibold small">Total</td>
                                <td class="sale-land-sheet__land-col fw-semibold">{{ $formulaTotals['total_land'] }}</td>
                                @foreach($formulaColumns as $column)
                                    @php
                                        $formula = $formulaTotals['formula_values'][$column['plot_key']] ?? null;
                                    @endphp
                                    <td class="text-end sale-land-sheet__formula-col">
                                        @if($formula)
                                            {{ $formula['display'] }}
                                            @if(($formula['breakdown'] ?? '—') !== '—')
                                                <div class="sale-land-sheet__formula-breakdown">{{ $formula['breakdown'] }}</div>
                                            @endif
                                        @else
                                            —
                                        @endif
                                    </td>
                                @endforeach
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
            <p class="text-muted small text-center py-4 mb-0 d-none" id="sale-land-search-empty">No rows match your search.</p>
        </div>
    </div>
@endif
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
        border-right: 2px solid #dee2e6;
    }
    .sale-land-sheet-split__scroll {
        flex: 1 1 auto;
        min-width: 0;
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }
    .sale-land-sheet {
        --sale-land-sr-width: 48px;
        --sale-land-file-width: 190px;
        margin-bottom: 0;
    }
    .sale-land-sheet thead th {
        font-size: 0.8rem;
        font-weight: 600;
        white-space: nowrap;
        vertical-align: bottom;
        background: #f5f5f5 !important;
    }
    .sale-land-sheet--scroll thead th {
        position: sticky;
        top: 0;
        z-index: 1;
    }
    .sale-land-sheet tbody td {
        font-size: 0.85rem;
        vertical-align: middle;
    }
    .sale-land-sheet__row--hidden {
        display: none;
    }
    .sale-land-sheet tbody tr.is-hovered td {
        background: #f8f9fa !important;
    }
    .sale-land-sheet__file-group-start td {
        border-top: 2px solid #adb5bd !important;
    }
    .sale-land-sheet__file-group-start:first-child td,
    .sale-land-sheet tbody tr:first-child td {
        border-top: 0 !important;
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
    .sale-land-sheet__file-name-wrap {
        display: flex;
        align-items: flex-start;
        gap: 0.4rem;
    }
    .sale-land-sheet__file-name-text {
        flex: 1 1 auto;
        min-width: 0;
        word-break: break-word;
        cursor: pointer;
        font-weight: 600;
    }
    .sale-land-file-check {
        flex: 0 0 auto;
        margin-top: 0.2rem;
    }
    .sale-land-sheet__file-delete-form {
        flex: 0 0 auto;
        margin-left: auto;
    }
    .sale-land-sheet__file-delete-btn {
        padding: 0.15rem 0.35rem;
        line-height: 1;
        font-size: 0.75rem;
    }
    .sale-land-pdf-link.is-loading {
        pointer-events: none;
        opacity: 0.65;
    }
    .sale-land-sheet__formula-code {
        font-size: 0.7rem;
        font-weight: 600;
        color: #6c757d;
    }
    .sale-land-sheet__formula-breakdown {
        font-size: 0.75rem;
        color: #6c757d;
        margin-top: 0.15rem;
        line-height: 1.3;
    }
    .sale-land-sheet__formula-col {
        min-width: 90px;
    }
    .sale-land-sheet__totals-row td {
        background: #f5f5f5 !important;
        border-top: 2px solid #6c757d !important;
        font-weight: 600;
    }
    .sale-land-sheet__land-col {
        white-space: nowrap;
        min-width: 130px;
    }
    .sale-land-sheet__editable {
        cursor: text;
        min-width: 100px;
    }
    .sale-land-sheet__editable.is-editing {
        padding: 0.25rem;
    }
    .sale-land-sheet__editable-input {
        width: 100%;
        min-width: 100px;
        font-size: 0.85rem;
        padding: 0.25rem 0.4rem;
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
        var frozenHeadRows = document.querySelectorAll('.sale-land-sheet--frozen thead tr');
        var scrollHeadRows = document.querySelectorAll('.sale-land-sheet--scroll thead tr');

        frozenHeadRows.forEach(function(fRow, i) {
            var sRow = scrollHeadRows[i];
            if (!sRow) {
                return;
            }
            fRow.style.height = '';
            sRow.style.height = '';
            var headH = Math.max(fRow.offsetHeight, sRow.offsetHeight);
            fRow.style.height = headH + 'px';
            sRow.style.height = headH + 'px';
        });

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

    document.querySelectorAll('.sale-land-sheet--scroll tbody tr').forEach(function(sRow) {
        var idx = sRow.dataset.rowIdx;
        var fRow = document.querySelector('.sale-land-sheet--frozen tbody tr[data-row-idx="' + idx + '"]');
        function setHover(on) {
            sRow.classList.toggle('is-hovered', on);
            if (fRow) {
                fRow.classList.toggle('is-hovered', on);
            }
        }
        sRow.addEventListener('mouseenter', function() { setHover(true); });
        sRow.addEventListener('mouseleave', function() { setHover(false); });
        if (fRow) {
            fRow.addEventListener('mouseenter', function() { setHover(true); });
            fRow.addEventListener('mouseleave', function() { setHover(false); });
        }
    });

    document.querySelectorAll('.sale-land-sheet__editable').forEach(function(cell) {
        cell.addEventListener('dblclick', function() {
            openEditor(cell);
        });
    });

    var searchInput = document.getElementById('sale-land-search');
    var searchCount = document.getElementById('sale-land-search-count');
    var searchEmpty = document.getElementById('sale-land-search-empty');
    var sheetSplit = document.getElementById('sale-land-sheet-split');
    var totalRows = document.querySelectorAll('.sale-land-sheet--scroll tbody tr').length;

    function applySaleLandSearch() {
        var q = searchInput ? searchInput.value.trim().toLowerCase() : '';
        var scrollRows = document.querySelectorAll('.sale-land-sheet--scroll tbody tr');
        var fileIdsToShow = new Set();
        var visibleCount = 0;

        scrollRows.forEach(function(row) {
            var haystack = row.dataset.searchText || '';
            var fileId = row.dataset.purchaseFileId;
            if (q === '' || haystack.indexOf(q) !== -1) {
                fileIdsToShow.add(fileId);
            }
        });

        if (q !== '') {
            document.querySelectorAll('.sale-land-sheet__file-name-text').forEach(function(label) {
                if (label.textContent.toLowerCase().indexOf(q) !== -1) {
                    var frozenRow = label.closest('tr');
                    if (frozenRow) {
                        var scrollRow = document.querySelector('.sale-land-sheet--scroll tbody tr[data-row-idx="' + frozenRow.dataset.rowIdx + '"]');
                        if (scrollRow) {
                            fileIdsToShow.add(scrollRow.dataset.purchaseFileId);
                        }
                    }
                }
            });
        }

        scrollRows.forEach(function(row) {
            var show = q === '' || fileIdsToShow.has(row.dataset.purchaseFileId);
            row.classList.toggle('sale-land-sheet__row--hidden', !show);
            if (show) {
                visibleCount++;
            }
        });

        document.querySelectorAll('.sale-land-sheet--frozen tbody tr').forEach(function(row) {
            var scrollRow = document.querySelector('.sale-land-sheet--scroll tbody tr[data-row-idx="' + row.dataset.rowIdx + '"]');
            var show = scrollRow && !scrollRow.classList.contains('sale-land-sheet__row--hidden');
            row.classList.toggle('sale-land-sheet__row--hidden', !show);
        });

        if (searchCount) {
            if (q === '') {
                searchCount.textContent = totalRows + ' mouza ' + (totalRows === 1 ? 'row' : 'rows');
            } else {
                searchCount.textContent = 'Showing ' + visibleCount + ' of ' + totalRows + ' rows';
            }
        }

        if (searchEmpty) {
            searchEmpty.classList.toggle('d-none', q === '' || visibleCount > 0);
        }

        if (sheetSplit) {
            sheetSplit.classList.toggle('d-none', q !== '' && visibleCount === 0);
        }

        syncSaleLandRowHeights();
    }

    if (searchInput) {
        searchInput.addEventListener('input', applySaleLandSearch);
        applySaleLandSearch();
    }

    var checkAllBtn = document.getElementById('sale-land-check-all');
    var checkNoneBtn = document.getElementById('sale-land-check-none');
    if (checkAllBtn) {
        checkAllBtn.addEventListener('click', function() {
            document.querySelectorAll('.sale-land-file-check').forEach(function(cb) {
                cb.checked = true;
            });
        });
    }
    if (checkNoneBtn) {
        checkNoneBtn.addEventListener('click', function() {
            document.querySelectorAll('.sale-land-file-check').forEach(function(cb) {
                cb.checked = false;
            });
        });
    }
})();
</script>
@endpush
@endif

@push('scripts')
<script>
(function() {
    function setPdfLinkLoading(link, loading) {
        if (loading) {
            if (!link.dataset.pdfOriginalHtml) {
                link.dataset.pdfOriginalHtml = link.innerHTML;
            }
            link.classList.add('is-loading', 'disabled');
            link.setAttribute('aria-disabled', 'true');
            link.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>';
        } else {
            link.classList.remove('is-loading', 'disabled');
            link.removeAttribute('aria-disabled');
            if (link.dataset.pdfOriginalHtml) {
                link.innerHTML = link.dataset.pdfOriginalHtml;
            }
        }
    }

    function buildPdfUrl(baseUrl) {
        var checked = document.querySelectorAll('.sale-land-file-check:checked');
        if (!checked.length) {
            return baseUrl;
        }
        var params = Array.from(checked).map(function(cb) {
            return 'purchase_file[]=' + encodeURIComponent(cb.value);
        });
        return baseUrl + (baseUrl.indexOf('?') === -1 ? '?' : '&') + params.join('&');
    }

    var pdfBtn = document.getElementById('sale-land-pdf-btn');
    if (pdfBtn) {
        pdfBtn.addEventListener('click', function(e) {
            e.preventDefault();
            if (pdfBtn.classList.contains('is-loading')) {
                return;
            }

            var baseUrl = pdfBtn.getAttribute('data-base-url');
            if (!baseUrl) {
                return;
            }

            var href = buildPdfUrl(baseUrl);
            setPdfLinkLoading(pdfBtn, true);

            fetch(href, { credentials: 'same-origin', headers: { Accept: 'application/pdf' } })
                .then(function(res) {
                    if (!res.ok) {
                        throw new Error('pdf');
                    }
                    var cd = res.headers.get('Content-Disposition');
                    var fname = 'sale-land.pdf';
                    if (cd) {
                        var mStar = /filename\*\s*=\s*UTF-8''([^;\s]+)/i.exec(cd);
                        var mQuot = /filename\s*=\s*"([^"]+)"/i.exec(cd);
                        var mPlain = /filename\s*=\s*([^;\s]+)/i.exec(cd);
                        if (mStar) fname = decodeURIComponent(mStar[1].replace(/"/g, ''));
                        else if (mQuot) fname = mQuot[1];
                        else if (mPlain) fname = mPlain[1].replace(/"/g, '');
                    }
                    return res.blob().then(function(blob) {
                        return { blob: blob, fname: fname };
                    });
                })
                .then(function(o) {
                    var url = URL.createObjectURL(o.blob);
                    var a = document.createElement('a');
                    a.href = url;
                    a.download = o.fname;
                    document.body.appendChild(a);
                    a.click();
                    a.remove();
                    setTimeout(function() {
                        URL.revokeObjectURL(url);
                    }, 2000);
                })
                .catch(function() {
                    alert('Could not download PDF. Please try again.');
                })
                .finally(function() {
                    setPdfLinkLoading(pdfBtn, false);
                });
        });
    }
})();
</script>
@endpush
