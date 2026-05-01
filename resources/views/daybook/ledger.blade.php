@extends('layouts.app')

@section('title', 'Daybook ledger')

@section('main_class', 'container-fluid px-3 pb-4 pt-0')

@push('head')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/flatpickr.min.css">
@endpush

@section('content')
@php
    $pdfQuery = ['from' => $from->format('Y-m-d'), 'to' => $to->format('Y-m-d')];
    if ($party_id) {
        $pdfQuery['party_id'] = $party_id;
    }
    $clearPartyQuery = ['from' => $from->format('Y-m-d'), 'to' => $to->format('Y-m-d')];
@endphp
<div class="no-print mb-3">
    <div class="d-flex flex-wrap align-items-end gap-3">
        <form method="get" action="{{ route('daybook.ledger') }}" class="d-flex flex-wrap align-items-end gap-3 flex-grow-1" id="ledger-filter-form">
            <div style="min-width: min(100%, 220px); max-width: 320px;">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-1">
                    <label class="form-label small text-muted mb-0" for="ledger_form_party_search">Party</label>
                    <a href="{{ route('daybook.index') }}" class="small fw-semibold text-decoration-none">+ Create on Daybook</a>
                </div>
                <div class="daybook-form-combo @error('party_id') is-invalid @enderror">
                    <input type="hidden" name="party_id" id="ledger_form_party_id" value="{{ $party_id ?: '' }}">
                    <input
                        type="text"
                        class="form-control form-control-theme @error('party_id') is-invalid @enderror"
                        id="ledger_form_party_search"
                        placeholder="Search party… (optional)"
                        autocomplete="off"
                        role="combobox"
                        aria-expanded="false"
                        aria-controls="ledger_form_party_listbox"
                        aria-autocomplete="list"
                    >
                    <ul class="daybook-form-combo-list d-none" id="ledger_form_party_listbox" role="listbox" hidden></ul>
                </div>
                @error('party_id')
                    <div class="invalid-feedback d-block">{{ $message }}</div>
                @enderror
            </div>
            <div>
                <label for="ledger-from" class="form-label small text-muted mb-0">From</label>
                <input type="text" name="from" id="ledger-from" class="form-control form-control-theme" value="{{ $from->format('Y-m-d') }}" autocomplete="off" required>
            </div>
            <div>
                <label for="ledger-to" class="form-label small text-muted mb-0">To</label>
                <input type="text" name="to" id="ledger-to" class="form-control form-control-theme" value="{{ $to->format('Y-m-d') }}" autocomplete="off" required>
            </div>
            <button type="submit" class="btn btn-theme">Show ledger</button>
            @if($party_id)
                <a class="btn btn-outline-theme" href="{{ route('daybook.ledger', $clearPartyQuery) }}">Clear party</a>
            @endif
        </form>
        <div class="d-flex flex-wrap gap-2">
            <button type="button" class="btn btn-outline-theme" onclick="window.print()">Print</button>
            <a class="btn btn-outline-theme" href="{{ route('daybook.ledger.pdf', $pdfQuery) }}">Download PDF</a>
            <a class="btn btn-outline-theme" href="{{ route('daybook.index') }}">Daybook</a>
        </div>
    </div>
    <script type="application/json" id="ledger-form-parties-json">@json($parties->map(function ($p) {
        return ['id' => $p->id, 'label' => $p->name];
    })->values())</script>
</div>

<div class="card card-theme daybook-ledger-print mb-4">
    <div class="card-body">
        <h1 class="h4 mb-1">Daybook ledger</h1>
        <p class="text-muted small mb-1">Period: {{ $from->format('l, j M Y') }} to {{ $to->format('l, j M Y') }}</p>
        @if($selectedParty)
            <p class="small mb-3"><span class="text-muted">Party:</span> <strong>{{ $selectedParty->name }}</strong> — showing daybook lines linked to this party only; balance is cumulative across the selected dates.</p>
        @else
            <p class="text-muted small mb-3">Full cash book (all parties and links).</p>
        @endif

        <div class="rounded border bg-light-subtle p-3 mb-4" style="border-color: rgba(15, 23, 42, 0.12) !important;">
            <div class="row g-2 text-center small">
                <div class="col-6 col-md-3">
                    <div class="text-muted">Payment in (range){{ $selectedParty ? ' · party' : '' }}</div>
                    <div class="fw-semibold text-success">Rs {{ number_format($grandCashIn, 0) }}</div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="text-muted">Payment out (range){{ $selectedParty ? ' · party' : '' }}</div>
                    <div class="fw-semibold text-danger">Rs {{ number_format($grandCashOut, 0) }}</div>
                </div>
            </div>
        </div>

        @forelse($ledgerDays as $L)
            @php
                $day = $L['day'];
                $prevDay = $L['prevDay'];
                $previousDayClosing = $L['previousDayClosing'];
                $openingAmount = $L['openingAmount'];
                $pettyCashAmount = $L['pettyCashAmount'];
                $cashIn = $L['cashIn'];
                $cashOut = $L['cashOut'];
                $closingBalance = $L['closingBalance'];
                $tableRows = $L['tableRows'];
                $partyFilter = ! empty($L['party_filter']);
                $partyRunningOpen = (float) ($L['party_running_open'] ?? 0);
            @endphp
            <section class="mb-4 pb-4 border-bottom" style="border-color: rgba(15, 23, 42, 0.1) !important;">
                <h2 class="h6 text-primary-emphasis mb-2">{{ $day->format('l, j M Y') }}</h2>

                @if($partyFilter)
                    @if($partyRunningOpen != 0.0)
                        <p class="small text-muted mb-2">Cumulative balance at start of day: <strong>Rs {{ number_format($partyRunningOpen, 0) }}</strong></p>
                    @endif
                    <div class="table-responsive mb-3">
                        <table class="table table-sm table-bordered mb-0">
                            <tbody>
                                <tr class="table-light">
                                    <th class="text-muted small" style="width:28%">Payment in (day)</th>
                                    <td class="text-success">Rs {{ number_format($cashIn, 0) }}</td>
                                    <th class="text-muted small" style="width:28%">Payment out (day)</th>
                                    <td class="text-danger">Rs {{ number_format($cashOut, 0) }}</td>
                                </tr>
                                <tr class="table-light">
                                    <th class="text-muted small" colspan="2">Closing (cumulative)</th>
                                    <td class="fw-bold text-success" colspan="2">Rs {{ number_format($closingBalance, 0) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                @else
                    <p class="small text-muted mb-2">Previous day ({{ $prevDay->format('l, j M Y') }}) closing: <strong>Rs {{ number_format($previousDayClosing, 0) }}</strong></p>

                    <div class="table-responsive mb-3">
                        <table class="table table-sm table-bordered mb-0">
                            <tbody>
                                <tr class="table-light">
                                    <th class="text-muted small" style="width:20%">Opening</th>
                                    <td>Rs {{ number_format($openingAmount, 0) }}</td>
                                    <th class="text-muted small" style="width:20%">Petty cash</th>
                                    <td>Rs {{ number_format($pettyCashAmount, 0) }}</td>
                                </tr>
                                <tr class="table-light">
                                    <th class="text-muted small">Payment in</th>
                                    <td class="text-success">Rs {{ number_format($cashIn, 0) }}</td>
                                    <th class="text-muted small">Payment out</th>
                                    <td class="text-danger">Rs {{ number_format($cashOut, 0) }}</td>
                                </tr>
                                <tr class="table-light">
                                    <th class="text-muted small" colspan="2">Closing balance</th>
                                    <td class="fw-bold text-success" colspan="2">Rs {{ number_format($closingBalance, 0) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                @endif

                <div class="table-responsive">
                    <table class="table table-theme table-sm mb-0">
                        <thead>
                            <tr>
                                <th>Description</th>
                                <th>Payment</th>
                                <th class="text-end">Amount</th>
                                <th class="text-end">Balance</th>
                            </tr>
                        </thead>
                        <tbody>
                            @if($partyFilter)
                                @if($partyRunningOpen != 0.0)
                                    <tr class="table-secondary">
                                        <td colspan="2">Brought forward</td>
                                        <td class="text-end"></td>
                                        <td class="text-end fw-medium">Rs {{ number_format($partyRunningOpen, 0) }}</td>
                                    </tr>
                                @endif
                            @else
                                <tr class="table-secondary">
                                    <td colspan="2">Opening balance (carried)</td>
                                    <td class="text-end"></td>
                                    <td class="text-end fw-medium">Rs {{ number_format($openingAmount, 0) }}</td>
                                </tr>
                                <tr class="table-secondary">
                                    <td colspan="2">Petty cash</td>
                                    <td class="text-end">Rs {{ number_format($pettyCashAmount, 0) }}</td>
                                    <td class="text-end fw-medium">Rs {{ number_format($openingAmount + $pettyCashAmount, 0) }}</td>
                                </tr>
                            @endif
                            @foreach($tableRows as $row)
                                <tr>
                                    <td>{{ $row['description'] }}</td>
                                    <td>{{ $row['type_label'] }}</td>
                                    <td class="text-end">{{ $row['amount_str'] }}</td>
                                    <td class="text-end">Rs {{ number_format($row['balance'], 0) }}</td>
                                </tr>
                            @endforeach
                            <tr class="fw-bold border-top border-2">
                                <td colspan="2">{{ $partyFilter ? 'Closing (cumulative)' : 'Closing balance' }}</td>
                                <td class="text-end"></td>
                                <td class="text-end text-success">Rs {{ number_format($closingBalance, 0) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>
        @empty
            <p class="text-muted mb-0">{{ $selectedParty ? 'No daybook lines linked to this party in this date range.' : 'No ledger rows for this range.' }}</p>
        @endforelse
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/flatpickr.min.js"></script>
<script>
(function () {
    if (typeof flatpickr === 'undefined') return;
    var opts = { dateFormat: 'Y-m-d', allowInput: false, disableMobile: true, clickOpens: true };
    var fromEl = document.getElementById('ledger-from');
    var toEl = document.getElementById('ledger-to');
    if (fromEl) flatpickr(fromEl, opts);
    if (toEl) flatpickr(toEl, opts);
})();

(function () {
    var partyHidden = document.getElementById('ledger_form_party_id');
    var partySearch = document.getElementById('ledger_form_party_search');
    var partyList = document.getElementById('ledger_form_party_listbox');
    var partyWrap = partySearch ? partySearch.closest('.daybook-form-combo') : null;
    var partyJsonEl = document.getElementById('ledger-form-parties-json');
    if (!partyHidden || !partySearch || !partyList) return;

    var formPartyRows = [];
    if (partyJsonEl) {
        try {
            formPartyRows = JSON.parse(partyJsonEl.textContent) || [];
        } catch (e) {
            formPartyRows = [];
        }
    }

    function hidePartyFormList() {
        partyList.classList.add('d-none');
        partyList.setAttribute('hidden', '');
        partySearch.setAttribute('aria-expanded', 'false');
    }

    function showPartyFormList() {
        partyList.classList.remove('d-none');
        partyList.removeAttribute('hidden');
        partySearch.setAttribute('aria-expanded', 'true');
    }

    function filterPartyFormRows(q) {
        var nq = (q || '').toLowerCase();
        if (!nq) return formPartyRows.slice();
        return formPartyRows.filter(function (row) {
            return (row.label || '').toLowerCase().indexOf(nq) !== -1;
        });
    }

    function renderPartyFormList(rows) {
        partyList.innerHTML = '';
        if (!rows.length) {
            var li0 = document.createElement('li');
            li0.className = 'daybook-form-combo-empty';
            li0.setAttribute('role', 'presentation');
            li0.textContent = formPartyRows.length ? 'No parties match.' : 'No parties yet.';
            partyList.appendChild(li0);
            showPartyFormList();
            return;
        }
        rows.forEach(function (row) {
            var li = document.createElement('li');
            li.setAttribute('role', 'none');
            var btn = document.createElement('button');
            btn.type = 'button';
            btn.setAttribute('role', 'option');
            btn.dataset.id = String(row.id);
            btn.textContent = row.label;
            btn.addEventListener('mousedown', function (e) {
                e.preventDefault();
            });
            btn.addEventListener('click', function () {
                partyHidden.value = String(row.id);
                partySearch.value = row.label;
                hidePartyFormList();
            });
            li.appendChild(btn);
            partyList.appendChild(li);
        });
        showPartyFormList();
    }

    function openFilteredPartyFormList() {
        renderPartyFormList(filterPartyFormRows(partySearch.value));
    }

    (function syncOldValues() {
        if (partyHidden.value) {
            var py = formPartyRows.find(function (r) { return String(r.id) === String(partyHidden.value); });
            if (py) partySearch.value = py.label;
        }
    })();

    partySearch.addEventListener('focus', function () {
        openFilteredPartyFormList();
    });
    partySearch.addEventListener('input', function () {
        partyHidden.value = '';
        openFilteredPartyFormList();
    });
    partySearch.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            e.stopPropagation();
            hidePartyFormList();
        }
    });

    document.addEventListener('click', function (e) {
        if (partyWrap && !partyWrap.contains(e.target)) hidePartyFormList();
    });
})();
</script>
@endpush
