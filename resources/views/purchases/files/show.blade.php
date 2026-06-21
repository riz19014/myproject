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
                <h2 class="h6 mb-0">Subcategories</h2>
            </div>
            <div class="card-body pf-ledger-nav">
                @if(count($ledgerTree) > 0)
                    <div class="pf-ledger-sub-list" role="radiogroup" aria-label="Subcategories">
                        @foreach($ledgerTree as $subCategory)
                            <label class="pf-ledger-sub-option">
                                <input type="radio"
                                       name="pf_ledger_sub"
                                       class="form-check-input pf-ledger-sub-radio"
                                       value="{{ $subCategory['key'] }}">
                                <span class="pf-ledger-sub-option__text">
                                    <span class="pf-ledger-sub-option__label">{{ $subCategory['label'] }}</span>
                                    <span class="pf-ledger-sub-option__meta">{{ $subCategory['category'] }}</span>
                                </span>
                            </label>
                        @endforeach
                    </div>

                    <div class="pf-ledger-party-panel d-none" id="pf-ledger-party-panel">
                        <p class="pf-ledger-party-panel__title">Parties</p>
                        <div class="pf-ledger-party-list" id="pf-ledger-party-list"></div>
                    </div>
                @else
                    <p class="text-muted small mb-0">No subcategories linked to this file yet.</p>
                @endif
            </div>
        </div>
    </div>

    <div class="col-lg-8 col-xl-9">
        <div class="card card-theme pf-ledger-panel h-100">
            <div class="card-body" id="pf-ledger-print-area">
                <p class="text-muted small mb-0 pf-ledger-empty" id="pf-ledger-empty">
                    Select a subcategory, then choose a party or “All parties” to view the ledger.
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
                                    <th style="width: 11%;">Date</th>
                                    <th style="width: 10%;">Voucher #</th>
                                    <th>Party</th>
                                    <th style="width: 14%;">Payment Method</th>
                                    <th class="text-end" style="width: 13%;">Debit (Payable)</th>
                                    <th class="text-end" style="width: 13%;">Credit (Paid)</th>
                                    <th class="text-end" style="width: 14%;">Running Balance</th>
                                </tr>
                            </thead>
                            <tbody id="pf-ledger-rows"></tbody>
                            <tfoot class="table-light">
                                <tr class="fw-semibold">
                                    <td colspan="4" id="pf-ledger-footer-label">Balance Payable</td>
                                    <td class="text-end font-monospace" id="pf-ledger-total-debit"></td>
                                    <td class="text-end font-monospace" id="pf-ledger-total-credit"></td>
                                    <td class="text-end font-monospace" id="pf-ledger-total-balance"></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
            <div class="card-footer pf-no-print d-flex flex-wrap gap-2">
                <button type="button" class="btn btn-outline-theme" id="pf-ledger-print-btn" disabled>Print</button>
                <a href="#"
                   class="btn btn-outline-theme pf-ledger-pdf-link d-inline-flex align-items-center gap-1 disabled"
                   id="pf-ledger-pdf-btn"
                   data-pdf-url="{{ route('purchase.files.ledger-pdf', $purchaseFile) }}"
                   aria-disabled="true"
                   title="Select a subcategory and party first">
                    <i class="bi bi-file-earmark-pdf" aria-hidden="true"></i>
                    <span class="pf-ledger-pdf-btn-text">Download PDF</span>
                </a>
            </div>
        </div>
    </div>
</div>

<script type="application/json" id="pf-ledger-json">@json(['sections' => $ledgerSections, 'tree' => $ledgerTree], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_INVALID_UTF8_SUBSTITUTE)</script>
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
        padding: 0.5rem 0;
    }
    .pf-ledger-sub-list {
        display: flex;
        flex-direction: column;
    }
    .pf-ledger-sub-option {
        display: flex;
        align-items: center;
        gap: 0.65rem;
        margin: 0;
        padding: 0.7rem 1rem;
        border-bottom: 1px solid #eef0f2;
        background: #fff;
        cursor: pointer;
        user-select: none;
        transition: background 0.15s ease;
    }
    .pf-ledger-sub-option:last-child {
        border-bottom: 0;
    }
    .pf-ledger-sub-option:hover {
        background: #fafafa;
    }
    .pf-ledger-sub-option:has(.pf-ledger-sub-radio:checked) {
        background: #fff8f3;
    }
    .pf-ledger-sub-option .form-check-input {
        flex: 0 0 auto;
        margin: 0;
    }
    .pf-ledger-sub-option__text {
        flex: 1 1 auto;
        min-width: 0;
    }
    .pf-ledger-sub-option__label {
        display: block;
        font-weight: 600;
        font-size: 0.875rem;
        color: #1a1a1a;
        line-height: 1.35;
    }
    .pf-ledger-sub-option__meta {
        display: block;
        font-size: 0.75rem;
        color: #888;
        margin-top: 0.15rem;
    }
    .pf-ledger-party-panel {
        margin: 0.75rem 1rem 1rem;
        padding: 0.75rem;
        background: #f8f9fa;
        border-radius: 0.5rem;
        border: 1px solid #e9ecef;
    }
    .pf-ledger-party-panel__title {
        margin: 0 0 0.5rem;
        font-size: 0.7rem;
        font-weight: 600;
        letter-spacing: 0.06em;
        text-transform: uppercase;
        color: #888;
    }
    .pf-ledger-party-list {
        display: flex;
        flex-direction: column;
        gap: 0.25rem;
    }
    .pf-ledger-party-item {
        display: block;
        width: 100%;
        text-align: left;
        border: 0;
        border-radius: 0.375rem;
        background: #fff;
        padding: 0.5rem 0.65rem;
        cursor: pointer;
        transition: background 0.15s ease, color 0.15s ease;
    }
    .pf-ledger-party-item:hover {
        background: #fff3eb;
    }
    .pf-ledger-party-item.is-active {
        background: #f97316;
        color: #fff;
    }
    .pf-ledger-party-item.is-active .pf-ledger-party-item__meta {
        color: rgba(255, 255, 255, 0.85);
    }
    .pf-ledger-party-item--all {
        border: 1px solid #f97316;
        margin-bottom: 0.35rem;
    }
    .pf-ledger-party-item--all .pf-ledger-party-item__label {
        font-weight: 600;
    }
    .pf-ledger-party-item__label {
        display: block;
        font-weight: 500;
        font-size: 0.875rem;
        line-height: 1.35;
    }
    .pf-ledger-party-item__meta {
        display: block;
        font-size: 0.72rem;
        color: #888;
        margin-top: 0.1rem;
    }
    .pf-ledger-table th,
    .pf-ledger-table td {
        vertical-align: top;
    }
    .pf-ledger-table tbody tr.is-opening td {
        background: #f8f9fa;
    }
    .pf-ledger-table tfoot td {
        background: #f0f0f0;
    }
    .pf-ledger-detail-lines {
        line-height: 1.45;
    }
    .pf-ledger-detail-line + .pf-ledger-detail-line {
        margin-top: 0.1rem;
    }
    .pf-ledger-detail-line--title {
        font-weight: 600;
        color: #212529;
    }
    .pf-ledger-detail-line--muted {
        font-size: 0.85rem;
    }
    .pf-ledger-payment-detail {
        font-size: 0.78rem;
        line-height: 1.25;
    }
    .pf-ledger-payment-detail span {
        display: block;
    }
    .pf-ledger-payment-detail__method {
        font-weight: 600;
        color: #212529;
    }
    .pf-ledger-payment-detail__meta {
        color: #495057;
    }
    .pf-ledger-payment-detail__desc {
        color: #6c757d;
        font-size: 0.72rem;
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
    .pf-ledger-pdf-link.is-loading,
    .pf-ledger-pdf-link.disabled {
        pointer-events: none;
        opacity: 0.65;
    }
</style>
@endpush

@push('scripts')
<script>
(function() {
    var jsonEl = document.getElementById('pf-ledger-json');
    var sections = {};
    var treeByKey = {};
    if (jsonEl && jsonEl.textContent) {
        try {
            var parsed = JSON.parse(jsonEl.textContent);
            sections = parsed.sections || {};
            (parsed.tree || []).forEach(function(node) {
                treeByKey[node.key] = node;
            });
        } catch (e) {
            sections = {};
            treeByKey = {};
        }
    }

    var emptyEl = document.getElementById('pf-ledger-empty');
    var contentEl = document.getElementById('pf-ledger-content');
    var titleEl = document.getElementById('pf-ledger-title');
    var subtitleEl = document.getElementById('pf-ledger-subtitle');
    var rowsEl = document.getElementById('pf-ledger-rows');
    var totalDebitEl = document.getElementById('pf-ledger-total-debit');
    var totalCreditEl = document.getElementById('pf-ledger-total-credit');
    var totalBalanceEl = document.getElementById('pf-ledger-total-balance');
    var footerLabelEl = document.getElementById('pf-ledger-footer-label');
    var printBtn = document.getElementById('pf-ledger-print-btn');
    var pdfBtn = document.getElementById('pf-ledger-pdf-btn');
    var partyListEl = document.getElementById('pf-ledger-party-list');
    var partyPanelEl = document.getElementById('pf-ledger-party-panel');
    var subRadios = document.querySelectorAll('.pf-ledger-sub-radio');
    var activeKey = null;

    function setLedgerActionsEnabled(enabled) {
        if (printBtn) {
            printBtn.disabled = !enabled;
        }
        if (pdfBtn) {
            pdfBtn.classList.toggle('disabled', !enabled);
            pdfBtn.setAttribute('aria-disabled', enabled ? 'false' : 'true');
        }
    }

    function resetLedgerView() {
        activeKey = null;
        emptyEl.classList.remove('d-none');
        contentEl.classList.add('d-none');
        setLedgerActionsEnabled(false);
    }

    function hidePartyPanel() {
        if (partyPanelEl) {
            partyPanelEl.classList.add('d-none');
        }
        if (partyListEl) {
            partyListEl.innerHTML = '';
        }
        resetLedgerView();
    }

    function setActiveParty(key) {
        if (!partyListEl) {
            return;
        }
        partyListEl.querySelectorAll('.pf-ledger-party-item[data-ledger-key]').forEach(function(btn) {
            btn.classList.toggle('is-active', btn.dataset.ledgerKey === key);
        });
    }

    function bindPartyButton(btn) {
        btn.addEventListener('click', function() {
            renderSection(btn.dataset.ledgerKey);
            setActiveParty(btn.dataset.ledgerKey);
        });
    }

    function renderPartyList(subKey) {
        if (!partyListEl || !partyPanelEl) {
            return;
        }
        var node = treeByKey[subKey];
        partyListEl.innerHTML = '';
        partyPanelEl.classList.remove('d-none');
        resetLedgerView();

        if (!node || !node.parties || !node.parties.length) {
            partyListEl.innerHTML = '<p class="text-muted small mb-0">No parties in this subcategory.</p>';
            return;
        }
        node.parties.forEach(function(party) {
            var btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'pf-ledger-party-item' + (party.is_all ? ' pf-ledger-party-item--all' : '');
            btn.dataset.ledgerKey = party.key;
            var label = document.createElement('span');
            label.className = 'pf-ledger-party-item__label';
            label.textContent = party.label || '';
            btn.appendChild(label);
            if (party.meta) {
                var meta = document.createElement('span');
                meta.className = 'pf-ledger-party-item__meta';
                meta.textContent = party.meta;
                btn.appendChild(meta);
            }
            bindPartyButton(btn);
            partyListEl.appendChild(btn);
        });
    }

    subRadios.forEach(function(radio) {
        radio.addEventListener('change', function() {
            if (radio.checked) {
                renderPartyList(radio.value);
            }
        });
    });

    function escapeHtml(text) {
        return String(text)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function formatLedgerAmount(value) {
        if (value === null || value === undefined || value === '') {
            return '—';
        }
        var n = Number(value);
        if (!isFinite(n)) {
            return '—';
        }
        return n.toLocaleString('en-PK', { minimumFractionDigits: 0, maximumFractionDigits: 0 });
    }

    function renderPaymentMethodCell(row) {
        var lines = row.payment_method_lines || [];
        if (!lines.length) {
            return escapeHtml(row.payment_method || '—');
        }
        var html = '<div class="pf-ledger-payment-detail">';
        lines.forEach(function(line) {
            var kind = line.kind || 'meta';
            html += '<span class="pf-ledger-payment-detail__' + kind + '">' + escapeHtml(line.text || '') + '</span>';
        });
        html += '</div>';
        return html;
    }

    function renderSection(key) {
        var section = sections[key];
        if (!section) {
            return;
        }

        activeKey = key;
        setActiveParty(key);

        emptyEl.classList.add('d-none');
        contentEl.classList.remove('d-none');
        setLedgerActionsEnabled(true);

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
                '<td>' + escapeHtml(row.date || '—') + '</td>' +
                '<td>' + escapeHtml(row.voucher || '—') + '</td>' +
                '<td>' + escapeHtml(row.party || '—') + '</td>' +
                '<td>' + renderPaymentMethodCell(row) + '</td>' +
                '<td class="text-end font-monospace">' + formatLedgerAmount(row.debit) + '</td>' +
                '<td class="text-end font-monospace">' + formatLedgerAmount(row.credit) + '</td>' +
                '<td class="text-end font-monospace">' + formatLedgerAmount(row.running_balance) + '</td>';
            rowsEl.appendChild(tr);
        });

        if (!section.rows || !section.rows.length) {
            var emptyRow = document.createElement('tr');
            emptyRow.innerHTML = '<td colspan="7" class="text-muted small">No ledger entries yet.</td>';
            rowsEl.appendChild(emptyRow);
        }

        var footer = section.footer || {};
        footerLabelEl.textContent = footer.label || 'Balance Payable';
        totalDebitEl.textContent = formatLedgerAmount(footer.debit);
        totalCreditEl.textContent = formatLedgerAmount(footer.credit);
        totalBalanceEl.textContent = formatLedgerAmount(footer.running_balance);
    }

    printBtn?.addEventListener('click', function() {
        if (!activeKey) {
            return;
        }
        window.print();
    });

    function setLedgerPdfLinkLoading(link, loading) {
        if (!link) {
            return;
        }
        if (loading) {
            if (!link.dataset.pdfOriginalHtml) {
                link.dataset.pdfOriginalHtml = link.innerHTML;
            }
            link.classList.add('is-loading', 'disabled');
            link.setAttribute('aria-disabled', 'true');
            link.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span><span class="ms-1">Preparing PDF…</span>';
        } else {
            link.classList.remove('is-loading', 'disabled');
            link.removeAttribute('aria-disabled');
            if (link.dataset.pdfOriginalHtml) {
                link.innerHTML = link.dataset.pdfOriginalHtml;
            }
        }
    }

    function bindLedgerPdfDownload(link) {
        if (!link) {
            return;
        }
        link.addEventListener('click', function(e) {
            e.preventDefault();
            if (link.classList.contains('is-loading') || link.classList.contains('disabled')) {
                return;
            }

            if (!activeKey) {
                alert('Select a subcategory, then choose a party or “All parties” to download its ledger PDF.');
                return;
            }

            var baseUrl = link.getAttribute('data-pdf-url');
            if (!baseUrl) {
                return;
            }

            var href = baseUrl + (baseUrl.indexOf('?') >= 0 ? '&' : '?') + 'section=' + encodeURIComponent(activeKey);

            setLedgerPdfLinkLoading(link, true);

            fetch(href, { credentials: 'same-origin', headers: { Accept: 'application/pdf' } })
                .then(function(res) {
                    if (!res.ok) {
                        throw new Error('pdf');
                    }
                    var cd = res.headers.get('Content-Disposition');
                    var fname = 'purchase-ledger.pdf';
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
                    setLedgerPdfLinkLoading(link, false);
                });
        });
    }

    bindLedgerPdfDownload(pdfBtn);
})();
</script>
@endpush
