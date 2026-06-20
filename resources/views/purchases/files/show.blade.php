@extends('layouts.app')

@section('title', $purchaseFile->file_name.' — Purchase file')

@section('content')
<div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4 pf-no-print">
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

<div class="row g-2 mb-3 pf-no-print">
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

<div class="row g-3 pf-ledger-layout">
    <div class="col-lg-4 col-xl-3 pf-no-print">
        <div class="card card-theme pf-ledger-nav-card h-100">
            <div class="card-header py-2">
                <h2 class="h6 mb-0">Ledger items</h2>
            </div>
            <div class="card-body p-0 pf-ledger-nav">
                @forelse($ledgerNavGrouped as $group => $items)
                    <div class="pf-ledger-nav-group">
                        <div class="pf-ledger-nav-group__title">{{ $group }}</div>
                        @foreach($items as $item)
                            <button type="button"
                                    class="pf-ledger-nav-item"
                                    data-ledger-key="{{ $item['key'] }}">
                                <span class="pf-ledger-nav-item__label">{{ $item['label'] }}</span>
                                @if(!empty($item['meta']))
                                    <span class="pf-ledger-nav-item__meta">{{ $item['meta'] }}</span>
                                @endif
                            </button>
                        @endforeach
                    </div>
                @empty
                    <p class="text-muted small mb-0 p-3">No parties, categories, or subcategories linked to this file yet.</p>
                @endforelse
            </div>
        </div>
    </div>

    <div class="col-lg-8 col-xl-9">
        <div class="card card-theme pf-ledger-panel h-100">
            <div class="card-body" id="pf-ledger-print-area">
                <p class="text-muted small mb-0 pf-ledger-empty" id="pf-ledger-empty">
                    Select a party, category, or subcategory on the left to view its ledger.
                </p>
                <div class="d-none" id="pf-ledger-content">
                    <div class="pf-ledger-print-header d-none d-print-block mb-3">
                        <h1 class="h5 mb-1">{{ $purchaseFile->file_name }}</h1>
                        <p class="text-muted small mb-0">
                            Project: {{ $purchaseFile->project?->name ?? '—' }}
                            · File date: {{ $purchaseFile->file_date?->format('d M Y') ?? '—' }}
                        </p>
                    </div>
                    <div class="mb-3">
                        <h2 class="h5 mb-1" id="pf-ledger-title"></h2>
                        <p class="text-muted small mb-0" id="pf-ledger-subtitle"></p>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered table-theme mb-0 pf-ledger-table">
                            <thead class="table-light">
                                <tr>
                                    <th>Details</th>
                                    <th class="text-end">Amount</th>
                                    <th class="text-end">Paid</th>
                                    <th class="text-end">Balance</th>
                                </tr>
                            </thead>
                            <tbody id="pf-ledger-rows"></tbody>
                            <tfoot class="table-light">
                                <tr class="fw-semibold">
                                    <td class="text-end">Total</td>
                                    <td class="text-end font-monospace" id="pf-ledger-total-amount"></td>
                                    <td class="text-end font-monospace" id="pf-ledger-total-paid"></td>
                                    <td class="text-end font-monospace" id="pf-ledger-total-balance"></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
            <div class="card-footer pf-no-print">
                <button type="button" class="btn btn-outline-theme" id="pf-ledger-print-btn" disabled>Print</button>
            </div>
        </div>
    </div>
</div>

<script type="application/json" id="pf-ledger-json">@json(['sections' => $ledgerSections], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_INVALID_UTF8_SUBSTITUTE)</script>
@endsection

@push('head')
<style>
    .pf-ledger-nav-card {
        max-height: calc(100vh - 12rem);
        display: flex;
        flex-direction: column;
    }
    .pf-ledger-nav {
        overflow-y: auto;
        flex: 1 1 auto;
    }
    .pf-ledger-nav-group + .pf-ledger-nav-group {
        border-top: 1px solid var(--border-dark, #dee2e6);
    }
    .pf-ledger-nav-group__title {
        padding: 0.55rem 0.85rem 0.35rem;
        font-size: 0.72rem;
        font-weight: 700;
        letter-spacing: 0.04em;
        text-transform: uppercase;
        color: #6c757d;
        background: #f8f9fa;
    }
    .pf-ledger-nav-item {
        display: block;
        width: 100%;
        border: 0;
        border-bottom: 1px solid #f1f3f5;
        background: #fff;
        text-align: left;
        padding: 0.65rem 0.85rem;
        transition: background 0.12s ease;
    }
    .pf-ledger-nav-item:hover,
    .pf-ledger-nav-item:focus {
        background: #fff7ed;
        outline: none;
    }
    .pf-ledger-nav-item.is-active {
        background: #fff7ed;
        box-shadow: inset 3px 0 0 #f97316;
    }
    .pf-ledger-nav-item__label {
        display: block;
        font-weight: 600;
        font-size: 0.88rem;
        color: #212529;
    }
    .pf-ledger-nav-item__meta {
        display: block;
        font-size: 0.78rem;
        color: #6c757d;
        margin-top: 0.1rem;
    }
    .pf-ledger-table th,
    .pf-ledger-table td {
        vertical-align: top;
    }
    .pf-ledger-table tbody tr.is-opening td {
        background: #f8f9fa;
    }
    @media print {
        .pf-no-print,
        .app-sidebar,
        .app-topbar { display: none !important; }
        .app-main,
        .pf-ledger-layout,
        .pf-ledger-panel,
        .pf-ledger-panel .card-body {
            display: block !important;
            width: 100% !important;
            max-width: 100% !important;
            margin: 0 !important;
            padding: 0 !important;
            border: 0 !important;
            box-shadow: none !important;
        }
        .pf-ledger-print-header { display: block !important; }
    }
</style>
@endpush

@push('scripts')
<script>
(function() {
    var jsonEl = document.getElementById('pf-ledger-json');
    var sections = {};
    if (jsonEl && jsonEl.textContent) {
        try {
            var parsed = JSON.parse(jsonEl.textContent);
            sections = parsed.sections || {};
        } catch (e) {
            sections = {};
        }
    }

    var emptyEl = document.getElementById('pf-ledger-empty');
    var contentEl = document.getElementById('pf-ledger-content');
    var titleEl = document.getElementById('pf-ledger-title');
    var subtitleEl = document.getElementById('pf-ledger-subtitle');
    var rowsEl = document.getElementById('pf-ledger-rows');
    var totalAmountEl = document.getElementById('pf-ledger-total-amount');
    var totalPaidEl = document.getElementById('pf-ledger-total-paid');
    var totalBalanceEl = document.getElementById('pf-ledger-total-balance');
    var printBtn = document.getElementById('pf-ledger-print-btn');
    var navItems = document.querySelectorAll('.pf-ledger-nav-item[data-ledger-key]');
    var activeKey = null;

    function formatMoney(value) {
        if (value === null || value === undefined || value === '') {
            return '—';
        }
        var n = Number(value);
        if (!isFinite(n)) {
            return '—';
        }
        if (n < 0) {
            return 'Overpaid Rs ' + Math.abs(n).toLocaleString('en-PK', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }
        return 'Rs ' + n.toLocaleString('en-PK', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    function renderSection(key) {
        var section = sections[key];
        if (!section) {
            return;
        }

        activeKey = key;
        navItems.forEach(function(btn) {
            btn.classList.toggle('is-active', btn.dataset.ledgerKey === key);
        });

        emptyEl.classList.add('d-none');
        contentEl.classList.remove('d-none');
        printBtn.disabled = false;

        titleEl.textContent = section.title || '';
        if (section.subtitle) {
            subtitleEl.textContent = section.subtitle;
            subtitleEl.classList.remove('d-none');
        } else {
            subtitleEl.textContent = '';
            subtitleEl.classList.add('d-none');
        }

        rowsEl.innerHTML = '';
        (section.rows || []).forEach(function(row) {
            var tr = document.createElement('tr');
            if (row.is_opening) {
                tr.classList.add('is-opening');
            }
            tr.innerHTML =
                '<td>' + (row.details || '—') + '</td>' +
                '<td class="text-end font-monospace">' + formatMoney(row.amount) + '</td>' +
                '<td class="text-end font-monospace">' + formatMoney(row.paid) + '</td>' +
                '<td class="text-end font-monospace">' + formatMoney(row.balance) + '</td>';
            rowsEl.appendChild(tr);
        });

        if (!section.rows || !section.rows.length) {
            var emptyRow = document.createElement('tr');
            emptyRow.innerHTML = '<td colspan="4" class="text-muted small">No ledger entries yet.</td>';
            rowsEl.appendChild(emptyRow);
        }

        var totals = section.totals || {};
        totalAmountEl.textContent = formatMoney(totals.amount);
        totalPaidEl.textContent = formatMoney(totals.paid);
        totalBalanceEl.textContent = formatMoney(totals.balance);
    }

    navItems.forEach(function(btn) {
        btn.addEventListener('click', function() {
            renderSection(btn.dataset.ledgerKey);
        });
    });

    printBtn?.addEventListener('click', function() {
        if (!activeKey) {
            return;
        }
        window.print();
    });

    if (navItems.length) {
        renderSection(navItems[0].dataset.ledgerKey);
    }
})();
</script>
@endpush
