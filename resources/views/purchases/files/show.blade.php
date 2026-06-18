@extends('layouts.app')

@section('title', $purchaseFile->file_name.' — Purchase file')

@section('content')
<div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
    <div>
        <h1 class="mb-1">{{ $purchaseFile->file_name }}</h1>
        <p class="text-muted small mb-1">
            Project: <strong>{{ $purchaseFile->project?->name ?? '—' }}</strong>
            · Date: <strong>{{ $purchaseFile->file_date?->format('d M Y') ?? '—' }}</strong>
            @if($purchaseFile->isSaleLand())
                · <span class="text-success">Sale land ({{ $purchaseFile->sale_land_at->format('d M Y') }})</span>
            @endif
        </p>
        @if($purchaseFile->dealers->isNotEmpty())
            <p class="text-muted small mb-0">
                Dealers:
                @foreach($purchaseFile->dealers as $dealer)
                    <strong>{{ $dealer->name }}</strong>
                    @if($dealer->pivot->commission_rs)
                        (Rs {{ number_format((int) $dealer->pivot->commission_rs, 0) }})
                    @endif
                    @if(!$loop->last), @endif
                @endforeach
            </p>
        @endif
    </div>
    <div class="d-flex flex-wrap gap-2 justify-content-end">
        <a href="{{ route('purchase.files.sellers', $purchaseFile) }}" class="btn btn-sm btn-outline-theme">Sellers</a>
        <a href="{{ route('purchase.files.documents', $purchaseFile) }}" class="btn btn-sm btn-outline-theme">
            Documents
            @if($purchaseFile->documents_count > 0)
                ({{ $purchaseFile->documents_count }})
            @endif
        </a>
        @if($purchaseFile->isSaleLand())
            <a href="{{ route('projects.sale-land', ['project' => $purchaseFile->project_id, 'purchase_file' => $purchaseFile->id]) }}" class="btn btn-sm btn-outline-theme">Sale land</a>
        @endif
        <a href="{{ route('purchase.files.edit', $purchaseFile) }}" class="btn btn-sm btn-outline-theme">Edit</a>
        <a href="{{ route('purchase.files.index', ['project' => $purchaseFile->project_id]) }}" class="btn btn-sm btn-outline-theme">Back to files</a>
    </div>
</div>

<div class="row g-2 mb-3">
    <div class="col-sm-6 col-lg-3">
        <div class="border rounded p-2 small bg-light h-100">
            <span class="text-muted d-block">Land total</span>
            <span class="fw-semibold">Rs {{ number_format($landTotalRs, 2) }}</span>
            @if($landAreaLabel !== '—')
                <span class="d-block text-muted mt-1">{{ $landAreaLabel }}</span>
            @endif
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="border rounded p-2 small bg-light h-100">
            <span class="text-muted d-block">Total paid</span>
            <span class="fw-semibold">Rs {{ number_format($totalPaid, 2) }}</span>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="border rounded p-2 small bg-light h-100">
            <span class="text-muted d-block">Balance payable</span>
            @if($balancePayable >= 0)
                <span class="fw-semibold">Rs {{ number_format($balancePayable, 2) }}</span>
            @else
                <span class="fw-semibold text-muted">Overpaid Rs {{ number_format(abs($balancePayable), 2) }}</span>
            @endif
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="border rounded p-2 small bg-light h-100">
            <span class="text-muted d-block">Subcategory expenses</span>
            <span class="fw-semibold">Rs {{ number_format($totalExpenses, 2) }}</span>
        </div>
    </div>
</div>

<div class="card card-theme mb-3">
    <div class="card-body py-3">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
            <p class="text-muted small mb-0">Select columns and rows below. View opens a spreadsheet layout; PDF and print match the same grid.</p>
            <div class="d-flex flex-wrap gap-2">
                <button type="button" class="btn btn-sm btn-outline-secondary" id="pf-section-check-all">Select all</button>
                <button type="button" class="btn btn-sm btn-outline-secondary" id="pf-section-check-none">Clear</button>
                <button type="button" class="btn btn-sm btn-pink" id="pf-section-view-btn">View selection</button>
                <button type="button" class="btn btn-sm btn-outline-theme" id="pf-section-print-btn">Print</button>
                <button type="button" class="btn btn-sm btn-outline-theme" id="pf-section-pdf-btn">Download PDF</button>
            </div>
        </div>
    </div>
</div>

<div class="pf-sheet-select-columns" id="pf-sheet-select-columns">
    @foreach($sheetGrid['columns'] as $column)
        @include('purchases.files.partials.show-sheet-column', ['column' => $column])
    @endforeach
</div>

<script type="application/json" id="pf-sheet-grid-json">@json($sheetGrid, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_INVALID_UTF8_SUBSTITUTE)</script>
@endsection

@push('modals')
<div class="modal fade" id="pfSectionViewModal" tabindex="-1" aria-labelledby="pfSectionViewModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable modal-fullscreen-lg-down" style="max-width: 96vw;">
        <div class="modal-content card-theme">
            <div class="modal-header">
                <h2 class="modal-title h5 mb-0" id="pfSectionViewModalLabel">{{ $purchaseFile->file_name }} — Sheet view</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="pf-section-print-area">
                <p class="text-muted small mb-3">
                    Project: <strong>{{ $purchaseFile->project?->name ?? '—' }}</strong>
                    · File date: <strong>{{ $purchaseFile->file_date?->format('d M Y') ?? '—' }}</strong>
                </p>
                <div id="pf-section-modal-sheet"></div>
            </div>
            <div class="modal-footer flex-wrap gap-2">
                <button type="button" class="btn btn-outline-theme" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-outline-theme" id="pf-section-modal-print-btn">Print</button>
                <button type="button" class="btn btn-pink" id="pf-section-modal-pdf-btn">Download PDF</button>
            </div>
        </div>
    </div>
</div>
@endpush

@push('head')
<style>
    .pf-sheet-select-columns {
        display: flex;
        gap: 0.75rem;
        overflow-x: auto;
        padding-bottom: 0.5rem;
        align-items: stretch;
    }
    .pf-sheet-select-col {
        flex: 0 0 200px;
        min-width: 200px;
        transition: box-shadow 0.15s ease, opacity 0.15s ease;
    }
    .pf-sheet-select-col.is-selected {
        box-shadow: 0 0 0 2px rgba(249, 115, 22, 0.35);
    }
    .pf-sheet-select-col:not(.is-selected) {
        opacity: 0.72;
    }
    .pf-sheet-select-col__head {
        background: #f8f9fa;
        padding: 0.65rem 0.75rem;
        border-bottom: 1px solid var(--border-dark, #dee2e6);
    }
    .pf-sheet-select-col__label {
        display: flex;
        align-items: center;
        gap: 0.45rem;
        font-weight: 600;
        font-size: 0.88rem;
        cursor: pointer;
        user-select: none;
    }
    .pf-sheet-select-col__body {
        max-height: 480px;
        overflow-y: auto;
        padding: 0.75rem;
    }
    .pf-section-stack {
        display: flex;
        flex-direction: column;
        gap: 0.55rem;
    }
    .pf-section-item {
        border: 1px solid #e9ecef;
        border-radius: 0.35rem;
        padding: 0.5rem 0.55rem;
        background: #fff;
        transition: opacity 0.15s ease;
    }
    .pf-section-item:not(.is-item-selected) {
        opacity: 0.45;
    }
    .pf-section-item__label {
        display: flex;
        align-items: flex-start;
        gap: 0.4rem;
        margin: 0;
        cursor: pointer;
        user-select: none;
    }
    .pf-section-item__label .form-check-input {
        flex: 0 0 auto;
        margin-top: 0.15rem;
    }
    .pf-section-item__content {
        flex: 1 1 auto;
        min-width: 0;
    }
    .pf-section-item__amount {
        font-weight: 600;
        font-size: 0.88rem;
    }
    .pf-section-total {
        border-top: 2px solid #adb5bd;
        padding-top: 0.5rem;
    }
    .pf-sheet-table th,
    .pf-sheet-table td {
        min-width: 72px;
    }
    @media print {
        body * { visibility: hidden; }
        #pf-section-print-area, #pf-section-print-area * { visibility: visible; }
        #pf-section-print-area {
            position: absolute;
            left: 0;
            top: 0;
            width: 100%;
            padding: 0.5rem;
        }
        .modal-backdrop { display: none !important; }
        .modal { position: static !important; display: block !important; overflow: visible !important; }
        .modal-dialog { max-width: 100% !important; margin: 0 !important; }
        .modal-header, .modal-footer { display: none !important; }
    }
    .is-loading { pointer-events: none; opacity: 0.65; }
</style>
@endpush

@push('scripts')
<script>
(function() {
    var pdfBaseUrl = @json(route('purchase.files.view-pdf', $purchaseFile));
    function readBaseGrid() {
        var el = document.getElementById('pf-sheet-grid-json');
        var raw = (el && el.textContent) ? el.textContent.trim() : '';
        if (!raw) {
            return { columns: [], row_count: 0 };
        }
        try {
            var parsed = JSON.parse(raw);
            return parsed && Array.isArray(parsed.columns) ? parsed : { columns: [], row_count: 0 };
        } catch (e) {
            return { columns: [], row_count: 0 };
        }
    }
    var baseGrid = readBaseGrid();
    var modalEl = document.getElementById('pfSectionViewModal');
    var modalSheet = document.getElementById('pf-section-modal-sheet');
    var modal = modalEl && typeof bootstrap !== 'undefined' ? bootstrap.Modal.getOrCreateInstance(modalEl) : null;

    function formatMoney(n) {
        var v = Math.abs(Number(n) || 0);
        var s = v.toLocaleString('en-PK', { maximumFractionDigits: 0 });
        return (Number(n) < 0 ? '-' : '') + s;
    }

    function getSelection() {
        var result = { columns: [], items: {} };
        document.querySelectorAll('.pf-sheet-select-col[data-column]').forEach(function(col) {
            var key = col.dataset.column;
            var parent = col.querySelector('.pf-column-check');
            if (!parent || !parent.checked) {
                return;
            }
            var itemIds = Array.from(col.querySelectorAll('.pf-item-check:checked')).map(function(cb) {
                return cb.value;
            });
            if (!itemIds.length && col.querySelectorAll('.pf-item-check').length > 0) {
                return;
            }
            result.columns.push(key);
            if (itemIds.length) {
                result.items[key] = itemIds;
            }
        });
        return result;
    }

    function requireSelection() {
        var selection = getSelection();
        if (!selection.columns.length) {
            alert('Select at least one column and one row.');
            return null;
        }
        return selection;
    }

    function filterGrid(grid, selection) {
        var columns = [];
        grid.columns.forEach(function(col) {
            if (selection.columns.indexOf(col.key) === -1) {
                return;
            }
            var rows = col.rows || [];
            if (selection.items[col.key]) {
                rows = rows.filter(function(row) {
                    return selection.items[col.key].indexOf(row.id) !== -1;
                });
            }
            var total = col.key === 'grand_total_exp'
                ? 0
                : rows.reduce(function(sum, row) { return sum + Number(row.amount || 0); }, 0);
            if (col.key === 'grand_total_exp') {
                selection.columns.forEach(function(key) {
                    if (key.indexOf('expense_') === 0) {
                        var src = grid.columns.find(function(c) { return c.key === key; });
                        if (!src) return;
                        var srcRows = src.rows || [];
                        if (selection.items[key]) {
                            srcRows = srcRows.filter(function(row) {
                                return selection.items[key].indexOf(row.id) !== -1;
                            });
                        }
                        total += srcRows.reduce(function(s, r) { return s + Number(r.amount || 0); }, 0);
                    }
                });
            }
            if (col.key === 'balance_payable' && rows.length) {
                total = Number(rows[rows.length - 1].amount || 0);
            }
            columns.push({
                key: col.key,
                label: col.label,
                full_label: col.full_label,
                rows: rows,
                total: total,
                total_display: formatMoney(total)
            });
        });
        var rowCount = 0;
        columns.forEach(function(col) {
            rowCount = Math.max(rowCount, col.rows.length);
        });
        return { columns: columns, row_count: rowCount };
    }

    function renderSheetTable(grid, target) {
        if (!target) return;
        if (!grid.columns.length) {
            target.innerHTML = '<p class="text-muted small mb-0">Nothing selected.</p>';
            return;
        }
        var html = '<div class="table-responsive"><table class="table table-bordered table-sm table-theme pf-sheet-table mb-0"><thead><tr>';
        grid.columns.forEach(function(col) {
            html += '<th class="text-center text-nowrap" title="' + (col.full_label || col.label) + '">' + col.label + '</th>';
        });
        html += '</tr></thead><tbody>';
        for (var r = 0; r < grid.row_count; r++) {
            html += '<tr>';
            grid.columns.forEach(function(col) {
                var row = col.rows[r];
                html += '<td class="text-end font-monospace small">' + (row ? row.display : '') + '</td>';
            });
            html += '</tr>';
        }
        html += '</tbody><tfoot class="table-light"><tr class="fw-semibold">';
        grid.columns.forEach(function(col) {
            html += '<td class="text-end font-monospace">' + col.total_display + '</td>';
        });
        html += '</tr></tfoot></table></div>';
        target.innerHTML = html;
    }

    function buildPdfUrl(selection) {
        var params = [];
        selection.columns.forEach(function(key) {
            params.push('columns[]=' + encodeURIComponent(key));
            if (selection.items[key]) {
                selection.items[key].forEach(function(id) {
                    params.push('items[' + encodeURIComponent(key) + '][]=' + encodeURIComponent(id));
                });
            }
        });
        return pdfBaseUrl + '?' + params.join('&');
    }

    function downloadPdf(selection, triggerBtn) {
        var href = buildPdfUrl(selection);
        if (triggerBtn) {
            triggerBtn.classList.add('is-loading');
            if (!triggerBtn.dataset.originalHtml) {
                triggerBtn.dataset.originalHtml = triggerBtn.innerHTML;
            }
            triggerBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';
        }
        fetch(href, { credentials: 'same-origin', headers: { Accept: 'application/pdf' } })
            .then(function(res) {
                if (!res.ok) throw new Error('pdf');
                var cd = res.headers.get('Content-Disposition');
                var fname = 'purchase-file.pdf';
                if (cd) {
                    var m = /filename="([^"]+)"/i.exec(cd) || /filename=([^;\s]+)/i.exec(cd);
                    if (m) fname = m[1].replace(/"/g, '');
                }
                return res.blob().then(function(blob) { return { blob: blob, fname: fname }; });
            })
            .then(function(o) {
                var url = URL.createObjectURL(o.blob);
                var a = document.createElement('a');
                a.href = url;
                a.download = o.fname;
                document.body.appendChild(a);
                a.click();
                a.remove();
                setTimeout(function() { URL.revokeObjectURL(url); }, 2000);
            })
            .catch(function() { alert('Could not download PDF.'); })
            .finally(function() {
                if (triggerBtn) {
                    triggerBtn.classList.remove('is-loading');
                    if (triggerBtn.dataset.originalHtml) {
                        triggerBtn.innerHTML = triggerBtn.dataset.originalHtml;
                    }
                }
            });
    }

    function syncParentState(col) {
        var parent = col.querySelector('.pf-column-check');
        var items = col.querySelectorAll('.pf-item-check');
        var checked = col.querySelectorAll('.pf-item-check:checked');
        if (!parent || !items.length) return;
        if (!checked.length) {
            parent.checked = false;
            parent.indeterminate = false;
        } else if (checked.length === items.length) {
            parent.checked = true;
            parent.indeterminate = false;
        } else {
            parent.checked = true;
            parent.indeterminate = true;
        }
    }

    function syncItemVisualState(col) {
        col.querySelectorAll('.pf-section-item[data-item-id]').forEach(function(item) {
            var cb = item.querySelector('.pf-item-check');
            item.classList.toggle('is-item-selected', !!(cb && cb.checked));
        });
    }

    function recalculateColumnTotal(col) {
        var key = col.dataset.column;
        var totalEl = col.querySelector('.pf-column-total-display');
        if (!totalEl) return;
        if (key === 'grand_total_exp') {
            var expenseSum = 0;
            document.querySelectorAll('.pf-sheet-select-col[data-column^="expense_"]').forEach(function(expCol) {
                var p = expCol.querySelector('.pf-column-check');
                if (!p || !p.checked) return;
                expCol.querySelectorAll('.pf-item-check:checked').forEach(function(cb) {
                    var item = cb.closest('.pf-section-item');
                    expenseSum += parseFloat(item?.dataset.amount || '0');
                });
            });
            totalEl.textContent = formatMoney(expenseSum);
            return;
        }
        var sum = 0;
        col.querySelectorAll('.pf-item-check:checked').forEach(function(cb) {
            var item = cb.closest('.pf-section-item');
            sum += parseFloat(item?.dataset.amount || '0');
        });
        if (key === 'balance_payable') {
            var checked = col.querySelectorAll('.pf-item-check:checked');
            if (checked.length) {
                var last = checked[checked.length - 1].closest('.pf-section-item');
                sum = parseFloat(last?.dataset.amount || '0');
            }
        }
        totalEl.textContent = formatMoney(sum);
    }

    function updateColumnSelectionState() {
        document.querySelectorAll('.pf-sheet-select-col[data-column]').forEach(function(col) {
            var parent = col.querySelector('.pf-column-check');
            var hasChecked = col.querySelectorAll('.pf-item-check').length === 0
                || col.querySelectorAll('.pf-item-check:checked').length > 0;
            col.classList.toggle('is-selected', !!(parent && parent.checked && hasChecked));
        });
    }

    function setAllChecks(checked) {
        document.querySelectorAll('.pf-column-check, .pf-item-check').forEach(function(cb) {
            cb.checked = checked;
            cb.indeterminate = false;
        });
        document.querySelectorAll('.pf-sheet-select-col[data-column]').forEach(function(col) {
            syncItemVisualState(col);
            recalculateColumnTotal(col);
        });
        updateColumnSelectionState();
    }

    document.querySelectorAll('.pf-column-check').forEach(function(parentCb) {
        parentCb.addEventListener('change', function() {
            var col = parentCb.closest('.pf-sheet-select-col');
            col.querySelectorAll('.pf-item-check').forEach(function(itemCb) {
                itemCb.checked = parentCb.checked;
            });
            parentCb.indeterminate = false;
            syncItemVisualState(col);
            recalculateColumnTotal(col);
            updateColumnSelectionState();
        });
    });

    document.querySelectorAll('.pf-item-check').forEach(function(itemCb) {
        itemCb.addEventListener('change', function() {
            var col = itemCb.closest('.pf-sheet-select-col');
            syncParentState(col);
            syncItemVisualState(col);
            recalculateColumnTotal(col);
            if (col.dataset.column && col.dataset.column.indexOf('expense_') === 0) {
                var grandCol = document.querySelector('.pf-sheet-select-col[data-column="grand_total_exp"]');
                if (grandCol) recalculateColumnTotal(grandCol);
            }
            updateColumnSelectionState();
        });
    });

    document.getElementById('pf-section-check-all')?.addEventListener('click', function() { setAllChecks(true); });
    document.getElementById('pf-section-check-none')?.addEventListener('click', function() { setAllChecks(false); });

    document.getElementById('pf-section-view-btn')?.addEventListener('click', function() {
        var selection = requireSelection();
        if (!selection) return;
        renderSheetTable(filterGrid(baseGrid, selection), modalSheet);
        modal?.show();
    });

    document.getElementById('pf-section-print-btn')?.addEventListener('click', function() {
        var selection = requireSelection();
        if (!selection) return;
        renderSheetTable(filterGrid(baseGrid, selection), modalSheet);
        modal?.show();
        setTimeout(function() { window.print(); }, 350);
    });

    document.getElementById('pf-section-pdf-btn')?.addEventListener('click', function() {
        var selection = requireSelection();
        if (!selection) return;
        downloadPdf(selection, this);
    });

    document.getElementById('pf-section-modal-print-btn')?.addEventListener('click', function() {
        window.print();
    });

    document.getElementById('pf-section-modal-pdf-btn')?.addEventListener('click', function() {
        var selection = getSelection();
        if (!selection.columns.length) {
            alert('Select at least one column and one row.');
            return;
        }
        downloadPdf(selection, this);
    });

    document.querySelectorAll('.pf-sheet-select-col[data-column]').forEach(function(col) {
        syncItemVisualState(col);
        recalculateColumnTotal(col);
    });
    updateColumnSelectionState();
})();
</script>
@endpush
