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
        <h1 class="h5 mb-2">Daybook ledger</h1>
        <p class="text-muted small mb-2">{{ $from->format('j M Y') }} — {{ $to->format('j M Y') }}@if($selectedParty) · <strong>{{ $selectedParty->name }}</strong>@endif</p>
        <p class="small text-muted mb-3">
            <span class="text-success">Payment in:</span> Rs {{ number_format($grandCashIn, 0) }}
            <span class="mx-2">·</span>
            <span class="text-danger">Payment out:</span> Rs {{ number_format($grandCashOut, 0) }}
        </p>

        <div class="table-responsive">
            <table class="table table-bordered table-sm mb-0 align-middle daybook-ledger-statement">
                <thead class="text-nowrap">
                    <tr class="table-dark">
                        <th scope="col" style="width:10%">Date</th>
                        <th scope="col" style="width:12%">Payment</th>
                        <th scope="col" class="text-end" style="width:12%">Amount</th>
                        <th scope="col">Description</th>
                        <th scope="col" class="text-end" style="width:12%">Balance</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($ledgerRows as $r)
                        <tr @class(['table-light' => !empty($r['is_meta'])])>
                            <td>{{ $r['date'] }}</td>
                            <td>{{ $r['payment'] }}</td>
                            <td class="text-end font-monospace">{{ $r['amount'] }}</td>
                            <td>{{ $r['description'] }}</td>
                            <td class="text-end font-monospace">Rs {{ number_format($r['balance'], 0) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-muted text-center py-4">{{ $selectedParty ? 'No lines for this party in this range.' : 'No rows for this range.' }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
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
