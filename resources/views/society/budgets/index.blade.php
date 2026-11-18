@extends('layouts.app')

@section('title', 'Budgets — '.$project->name)

@section('content')
@include('society.partials.nav')

@php
    $expenseCount = $expenseLedger->count();
    $landCount = $expenseLedger->filter(fn ($e) => $e->isLandAcquisitionPayment())->count();
    $developmentCount = $expenseCount - $landCount;
    $projectVariance = (float) $totalEstimated - (float) $developmentExpenseOut;
@endphp

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
    <div>
        <h1 class="h3 mb-1">Expense &amp; budget</h1>
        <p class="text-muted mb-0">Budget variance uses development expense only (excludes land / purchase-file payments). Line Actual still uses mapped subcategory when set.</p>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <button
            type="button"
            class="btn btn-outline-theme btn-sm"
            data-bs-toggle="modal"
            data-bs-target="#societyExpenseLedgerModal"
            data-ledger-filter="development"
        >View expense ledger</button>
        <a href="{{ route('daybook.index') }}" class="btn btn-outline-theme btn-sm">Open daybook</a>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-6 col-xl-3"><div class="card card-theme"><div class="card-body">
        <div class="text-muted small">Total estimated</div>
        <div class="fw-semibold">Rs {{ number_format($totalEstimated, 2) }}</div>
    </div></div></div>
    <div class="col-md-6 col-xl-3">
        <button
            type="button"
            class="card card-theme w-100 text-start border-0 p-0"
            data-bs-toggle="modal"
            data-bs-target="#societyExpenseLedgerModal"
            data-ledger-filter="all"
            title="Open full cash-out ledger"
        >
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start gap-2">
                    <div class="text-muted small">Total daybook cash-out</div>
                    <span class="badge text-bg-light border">{{ $expenseCount }} lines</span>
                </div>
                <div class="fw-semibold">Rs {{ number_format($totalActualOut, 2) }}</div>
                <div class="small text-muted mt-1">Land {{ number_format($landAcquisitionOut, 0) }} · Expense {{ number_format($developmentExpenseOut, 0) }}</div>
            </div>
        </button>
    </div>
    <div class="col-md-6 col-xl-3">
        <button
            type="button"
            class="card card-theme w-100 text-start border-0 p-0"
            data-bs-toggle="modal"
            data-bs-target="#societyExpenseLedgerModal"
            data-ledger-filter="development"
            title="Open development expense ledger"
        >
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start gap-2">
                    <div class="text-muted small">Development expense</div>
                    <span class="badge text-bg-light border">{{ $developmentCount }} lines</span>
                </div>
                <div class="fw-semibold">Rs {{ number_format($developmentExpenseOut, 2) }}</div>
                <div class="small text-muted mt-1">Excludes land / purchase-file payments</div>
            </div>
        </button>
    </div>
    <div class="col-md-6 col-xl-3"><div class="card card-theme"><div class="card-body">
        <div class="text-muted small">Budget variance (est − development expense)</div>
        <div class="fw-semibold {{ $projectVariance < 0 ? 'text-danger' : '' }}">Rs {{ number_format($projectVariance, 2) }}</div>
        <div class="small text-muted mt-1">Land acquisition out: Rs {{ number_format($landAcquisitionOut, 2) }}</div>
    </div></div></div>
</div>

<div class="row g-4">
    <div class="col-lg-4">
        <div class="card card-theme">
            <div class="card-body">
                <h5 class="mb-3">Add / update budget line</h5>
                <form method="post" action="{{ route('society.budgets.store', $project) }}">
                    @csrf
                    <div class="mb-2">
                        <label class="form-label">Category name</label>
                        <input name="category" class="form-control" required placeholder="e.g. Roads, Sewerage">
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Estimated amount</label>
                        <input type="number" step="0.01" min="0" name="estimated_amount" class="form-control" required>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Map to party subcategory (for actuals)</label>
                        <select name="party_sub_category_id" class="form-select">
                            <option value="">— Optional —</option>
                            @foreach($subCategories as $sc)
                                <option value="{{ $sc->id }}">{{ $sc->category?->name }} — {{ $sc->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Notes</label>
                        <textarea name="notes" class="form-control" rows="2"></textarea>
                    </div>
                    <button class="btn btn-pink btn-sm">Save</button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="card card-theme">
            <div class="card-body table-responsive">
                <table class="table table-theme align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Category</th>
                            <th>Mapped subcategory</th>
                            <th class="text-end">Estimated</th>
                            <th class="text-end">Actual</th>
                            <th class="text-end">Variance</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($rows as $row)
                            <tr>
                                <td class="fw-semibold">{{ $row['budget']->category }}</td>
                                <td class="small">{{ $row['budget']->partySubCategory?->name ?? '—' }}</td>
                                <td class="text-end">{{ number_format($row['budget']->estimated_amount, 2) }}</td>
                                <td class="text-end">{{ number_format($row['actual'], 2) }}</td>
                                <td class="text-end {{ $row['variance'] < 0 ? 'text-danger' : '' }}">{{ number_format($row['variance'], 2) }}</td>
                                <td class="text-end">
                                    <form method="post" action="{{ route('society.budgets.destroy', [$project, $row['budget']]) }}">
                                        @csrf @method('DELETE')
                                        <button type="button" class="btn btn-sm btn-outline-danger btn-delete-confirm" data-title="Remove budget line?" data-text="Remove this budget category from the list?" data-confirm="Yes, remove">Remove</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-muted text-center">No budget lines yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

@endsection

{{-- Outside <main> so backdrop does not cover / block the dialog --}}
@push('modals')
<div class="modal fade" id="societyExpenseLedgerModal" tabindex="-1" aria-labelledby="societyExpenseLedgerModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content society-expense-ledger-modal-content">
            <div class="modal-header">
                <div>
                    <h2 class="modal-title h5 mb-1" id="societyExpenseLedgerModalLabel">Cash-out ledger — {{ $project->name }}</h2>
                    <p class="text-muted small mb-0">Payment Out for this project. Land = purchase file / land link; Expense = everything else.</p>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0">
                <div class="px-3 pt-3 pb-2 d-flex flex-wrap gap-2 align-items-center justify-content-between">
                    <div class="btn-group btn-group-sm" role="group" aria-label="Ledger kind filter">
                        <button type="button" class="btn btn-outline-theme society-ledger-kind-btn active" data-ledger-kind="all">All ({{ $expenseCount }})</button>
                        <button type="button" class="btn btn-outline-theme society-ledger-kind-btn" data-ledger-kind="development">Expense ({{ $developmentCount }})</button>
                        <button type="button" class="btn btn-outline-theme society-ledger-kind-btn" data-ledger-kind="land">Land ({{ $landCount }})</button>
                    </div>
                    <div class="input-group input-group-sm" style="max-width: 22rem;">
                        <span class="input-group-text">Search</span>
                        <input type="search" class="form-control" id="society-expense-ledger-search" placeholder="Date, voucher, description, subcategory…" autocomplete="off">
                    </div>
                    <div class="small text-muted" id="society-expense-ledger-hint" aria-live="polite">
                        Showing {{ $expenseCount }} of {{ $expenseCount }}
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-theme table-sm align-middle mb-0" id="society-expense-ledger-table">
                        <thead>
                            <tr>
                                <th style="width: 3rem;">#</th>
                                <th>Date</th>
                                <th>Voucher</th>
                                <th>Kind</th>
                                <th>Description</th>
                                <th>Sub category</th>
                                <th>Settlement</th>
                                <th>Linked to</th>
                                <th class="text-end">Amount (Rs)</th>
                                <th class="text-end">Running</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @php
                                $runningAll = 0.0;
                                $runningDev = 0.0;
                                $runningLand = 0.0;
                            @endphp
                            @forelse($expenseLedger as $e)
                                @php
                                    $isLand = $e->isLandAcquisitionPayment();
                                    $kind = $isLand ? 'land' : 'development';
                                    $runningAll += (float) $e->amount;
                                    if ($isLand) {
                                        $runningLand += (float) $e->amount;
                                        $runningKind = $runningLand;
                                    } else {
                                        $runningDev += (float) $e->amount;
                                        $runningKind = $runningDev;
                                    }
                                    $subLabel = $e->getPartySubCategoryLabel();
                                    $entryDateYmd = $e->entry_date ? $e->entry_date->format('Y-m-d') : '';
                                    $entryDateLabel = $e->entry_date ? $e->entry_date->format('j M Y') : '—';
                                    $pfLabel = $e->getPurchaseFileLabel();
                                    $searchBlob = mb_strtolower(implode(' ', array_filter([
                                        $entryDateYmd,
                                        $entryDateLabel,
                                        $e->getVoucherNumber(),
                                        $e->description,
                                        $subLabel,
                                        $e->getSettlementLabel(),
                                        $e->getLinkLabel(),
                                        $pfLabel,
                                        $kind,
                                        $isLand ? 'land acquisition purchase' : 'development expense',
                                        (string) $e->amount,
                                    ])), 'UTF-8');
                                @endphp
                                <tr
                                    data-expense-search="{{ $searchBlob }}"
                                    data-ledger-kind="{{ $kind }}"
                                    data-running-all="{{ number_format($runningAll, 0) }}"
                                    data-running-kind="{{ number_format($runningKind, 0) }}"
                                >
                                    <td class="text-muted small society-ledger-row-num">{{ $loop->iteration }}</td>
                                    <td class="text-nowrap small">{{ $entryDateLabel }}</td>
                                    <td class="font-monospace small">{{ $e->getVoucherNumber() }}</td>
                                    <td class="small">
                                        @if($isLand)
                                            <span class="badge text-bg-warning-subtle border">Land</span>
                                            @if($pfLabel !== '—')
                                                <div class="text-muted" style="font-size: 0.7rem;">{{ $pfLabel }}</div>
                                            @endif
                                        @else
                                            <span class="badge text-bg-success-subtle border">Expense</span>
                                        @endif
                                    </td>
                                    <td class="small">{{ $e->description ?: '—' }}</td>
                                    <td class="small">{{ $subLabel }}</td>
                                    <td class="small">{{ $e->getSettlementLabel() }}</td>
                                    <td class="small">{{ $e->getLinkLabel() }}</td>
                                    <td class="text-end font-monospace fw-semibold text-danger">−{{ number_format((float) $e->amount, 0) }}</td>
                                    <td class="text-end font-monospace small society-ledger-running">{{ number_format($runningAll, 0) }}</td>
                                    <td class="text-end text-nowrap">
                                        <a href="{{ route('daybook.show', $e) }}" class="btn btn-sm btn-outline-theme" target="_blank" rel="noopener">View</a>
                                    </td>
                                </tr>
                            @empty
                                <tr class="society-ledger-empty">
                                    <td colspan="11" class="text-muted text-center py-4">No Payment Out entries for this project yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                        @if($expenseLedger->isNotEmpty())
                            <tfoot>
                                <tr>
                                    <th colspan="8" class="text-end" id="society-expense-ledger-total-label">Total (all)</th>
                                    <th class="text-end font-monospace text-danger" id="society-expense-ledger-total">−{{ number_format($totalActualOut, 0) }}</th>
                                    <th colspan="2"></th>
                                </tr>
                            </tfoot>
                        @endif
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-theme" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
@endpush

@push('head')
<style>
    #societyExpenseLedgerModal {
        z-index: 1080;
    }
    .society-expense-ledger-modal-content {
        background: #ffffff !important;
        border: 1px solid rgba(15, 23, 42, 0.12);
        border-radius: 12px;
        box-shadow: 0 24px 60px rgba(15, 23, 42, 0.25);
        color: #0f172a;
        overflow: hidden;
    }
    .society-expense-ledger-modal-content .modal-header,
    .society-expense-ledger-modal-content .modal-body,
    .society-expense-ledger-modal-content .modal-footer {
        background: #ffffff !important;
    }
    .society-expense-ledger-modal-content .modal-header {
        background: #f8fafc !important;
        border-bottom: 1px solid rgba(15, 23, 42, 0.08);
    }
    .society-expense-ledger-modal-content .modal-footer {
        border-top: 1px solid rgba(15, 23, 42, 0.08);
    }
    .society-ledger-kind-btn.active {
        background: var(--accent-orange, #f97316) !important;
        border-color: var(--accent-orange, #f97316) !important;
        color: #fff !important;
    }
</style>
@endpush

@push('scripts')
<script>
(function () {
    var modal = document.getElementById('societyExpenseLedgerModal');
    var input = document.getElementById('society-expense-ledger-search');
    var hint = document.getElementById('society-expense-ledger-hint');
    var totalLabel = document.getElementById('society-expense-ledger-total-label');
    var totalEl = document.getElementById('society-expense-ledger-total');
    var kindBtns = Array.prototype.slice.call(document.querySelectorAll('.society-ledger-kind-btn'));
    var rows = Array.prototype.slice.call(document.querySelectorAll('#society-expense-ledger-table tbody tr[data-ledger-kind]'));
    if (!modal || !rows.length) return;

    var totals = {
        all: {{ json_encode(number_format($totalActualOut, 0)) }},
        development: {{ json_encode(number_format($developmentExpenseOut, 0)) }},
        land: {{ json_encode(number_format($landAcquisitionOut, 0)) }}
    };
    var labels = {
        all: 'Total (all)',
        development: 'Total (development expense)',
        land: 'Total (land acquisition)'
    };
    var currentKind = 'all';

    function setKind(kind) {
        currentKind = kind || 'all';
        kindBtns.forEach(function (btn) {
            btn.classList.toggle('active', btn.getAttribute('data-ledger-kind') === currentKind);
        });
        run();
    }

    function run() {
        var q = (input && input.value || '').toLowerCase().trim();
        var n = 0;
        var visibleIdx = 0;
        rows.forEach(function (tr) {
            var kind = tr.getAttribute('data-ledger-kind') || '';
            var blob = tr.getAttribute('data-expense-search') || '';
            var kindOk = currentKind === 'all' || kind === currentKind;
            var searchOk = !q || blob.indexOf(q) !== -1;
            var ok = kindOk && searchOk;
            tr.classList.toggle('d-none', !ok);
            if (ok) {
                n++;
                visibleIdx++;
                var num = tr.querySelector('.society-ledger-row-num');
                if (num) num.textContent = String(visibleIdx);
                var running = tr.querySelector('.society-ledger-running');
                if (running) {
                    running.textContent = currentKind === 'all'
                        ? (tr.getAttribute('data-running-all') || '')
                        : (tr.getAttribute('data-running-kind') || '');
                }
            }
        });
        if (hint) {
            hint.textContent = 'Showing ' + n + ' of ' + rows.length
                + (currentKind !== 'all' ? ' · ' + currentKind : '')
                + (q ? ' (search)' : '');
        }
        if (totalLabel) totalLabel.textContent = labels[currentKind] || labels.all;
        if (totalEl) totalEl.textContent = '−' + (totals[currentKind] || totals.all);
    }

    if (input) input.addEventListener('input', run);
    kindBtns.forEach(function (btn) {
        btn.addEventListener('click', function () {
            setKind(btn.getAttribute('data-ledger-kind'));
        });
    });

    modal.addEventListener('show.bs.modal', function (ev) {
        var trigger = ev.relatedTarget;
        var filter = trigger && trigger.getAttribute('data-ledger-filter');
        if (input) input.value = '';
        setKind(filter || 'all');
    });
})();
</script>
@endpush
