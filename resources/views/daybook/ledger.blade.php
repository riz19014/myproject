@extends('layouts.app')

@section('title', 'Daybook ledger')

@section('main_class', 'container-fluid px-3 pb-4 pt-0')

@push('head')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/flatpickr.min.css">
    <style>
        .ledger-loading-overlay {
            position: fixed;
            inset: 0;
            z-index: 2000;
            background: rgba(15, 23, 42, 0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            flex-direction: column;
            gap: 0.75rem;
        }
        .ledger-loading-overlay.d-none {
            display: none !important;
        }
        .ledger-loading-overlay .ledger-loading-overlay__label {
            color: #fff;
            font-size: 0.95rem;
            font-weight: 500;
            margin: 0;
        }
        .daybook-ledger-statement tfoot .ledger-footer-totals {
            vertical-align: top;
            background: #f8fafc;
            line-height: 1.55;
            font-size: 0.8125rem;
        }
        .daybook-ledger-statement tfoot .ledger-footer-line + .ledger-footer-line {
            margin-top: 0.2rem;
        }
    </style>
@endpush

@section('content')
@php
    $ledger_ready = $ledger_ready ?? false;
    $pdfQuery = ['from' => $from->format('Y-m-d'), 'to' => $to->format('Y-m-d')];
    if ($ledger_ready && $party_id) {
        $pdfQuery['party_id'] = $party_id;
    }
    $clearPartyQuery = ['from' => $from->format('Y-m-d'), 'to' => $to->format('Y-m-d')];
@endphp
<div class="no-print mb-3">
    <div class="d-flex flex-wrap align-items-end gap-3">
        <form method="get" action="{{ route('daybook.ledger') }}" class="d-flex flex-wrap align-items-end gap-3 flex-grow-1" id="ledger-filter-form">
            <input type="hidden" name="_ledger" value="1">
            <div style="min-width: min(100%, 220px); max-width: 320px;">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-1">
                    <label class="form-label small text-muted mb-0" for="ledger_form_party_search">Party <span class="text-danger">*</span></label>
                    <a href="{{ route('daybook.index') }}" class="small fw-semibold text-decoration-none">+ Create on Daybook</a>
                </div>
                <div class="daybook-form-combo @error('party_id') is-invalid @enderror">
                    <input type="hidden" name="party_id" id="ledger_form_party_id" value="{{ old('party_id', $party_id ?: '') }}">
                    <input
                        type="text"
                        class="form-control form-control-theme @error('party_id') is-invalid @enderror"
                        id="ledger_form_party_search"
                        placeholder="Search party…"
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
            <a class="btn btn-outline-theme" id="ledger-pdf-link" href="{{ $ledger_ready ? route('daybook.ledger.pdf', $pdfQuery) : '#' }}">Download PDF</a>
            <a class="btn btn-outline-theme" href="{{ route('daybook.index') }}">Daybook</a>
        </div>
    </div>
    <script type="application/json" id="ledger-form-parties-json">@json($parties->map(function ($p) {
        return ['id' => $p->id, 'label' => $p->name];
    })->values())</script>
</div>

<div id="ledger-loading-overlay" class="ledger-loading-overlay d-none no-print" role="status" aria-live="polite" aria-busy="true" aria-hidden="true">
    <div class="spinner-border text-light" style="width: 2.75rem; height: 2.75rem;" aria-hidden="true"></div>
    <p class="ledger-loading-overlay__label" id="ledger-loading-overlay-label">Loading…</p>
</div>

<div class="card card-theme daybook-ledger-print mb-4">
    <div class="card-body">
        <h1 class="h5 mb-2">Daybook ledger</h1>
        <p class="text-muted small mb-2">{{ $from->format('j M Y') }} — {{ $to->format('j M Y') }}@if($selectedParty) · <strong>{{ $selectedParty->name }}</strong>@endif</p>
        @if($ledger_ready && ($grandCashIn > 0 || $grandCashOut > 0 || $openingBalanceSummary != 0.0))
            <p class="small text-muted mb-3">
                @php $sep = false; @endphp
                @if($grandCashIn > 0)
                    @if($sep)<span class="mx-2">·</span>@endif
                    <span class="text-success">Payment in:</span> Rs {{ number_format($grandCashIn, 0) }}
                    @php $sep = true; @endphp
                @endif
                @if($grandCashOut > 0)
                    @if($sep)<span class="mx-2">·</span>@endif
                    <span class="text-danger">Payment out:</span> Rs {{ number_format($grandCashOut, 0) }}
                    @php $sep = true; @endphp
                @endif
                @if($openingBalanceSummary != 0.0)
                    @if($sep)<span class="mx-2">·</span>@endif
                    <span class="text-body">Opening balance:</span> {{ $openingBalanceSummaryDisplay }}
                @endif
            </p>
        @endif

        <div class="table-responsive">
            <table class="table table-bordered table-sm mb-0 align-middle daybook-ledger-statement">
                <thead class="text-nowrap">
                    <tr class="table-dark">
                        <th scope="col" style="width:10%">Date</th>
                        <th scope="col" style="width:12%">Payment</th>
                        <th scope="col">Description</th>
                        <th scope="col" class="text-end" style="width:12%">Amount (Rs.)</th>
                        <th scope="col" class="text-end" style="width:12%">Balance (Rs.)</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($ledgerRows as $r)
                        <tr @class(['table-light' => !empty($r['is_meta'])])>
                            <td>{{ $r['date'] }}</td>
                            <td>{{ $r['payment'] }}</td>
                            <td>{{ $r['description'] }}</td>
                            <td class="text-end font-monospace">{{ $r['amount'] }}</td>
                            <td class="text-end font-monospace">{{ $r['balance_display'] }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-muted text-center py-4">
                                @if(!$ledger_ready)
                                    Please select a party to view the ledger.
                                @else
                                    No lines for this party in this range.
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                @if(count($ledgerFooter))
                <tfoot>
                    <tr class="border-top border-2">
                        <td colspan="3" class="border-end-0 bg-transparent"></td>
                        <td colspan="2" class="ledger-footer-totals text-end border-start">
                            @foreach($ledgerFooter as $line)
                                <div class="ledger-footer-line"><strong>{{ $line['label'] }}:</strong> {{ $line['value'] }}</div>
                            @endforeach
                        </td>
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/flatpickr.min.js"></script>
<script>
(function () {
    var overlay = document.getElementById('ledger-loading-overlay');
    var overlayLabel = document.getElementById('ledger-loading-overlay-label');
    var form = document.getElementById('ledger-filter-form');
    var submitBtn = form ? form.querySelector('button[type="submit"]') : null;
    var submitHtml = submitBtn ? submitBtn.innerHTML : '';

    function showOverlay(text) {
        if (overlayLabel && text) overlayLabel.textContent = text;
        if (overlay) {
            overlay.classList.remove('d-none');
            overlay.setAttribute('aria-hidden', 'false');
        }
    }

    function hideOverlay() {
        if (overlay) {
            overlay.classList.add('d-none');
            overlay.setAttribute('aria-hidden', 'true');
        }
        if (overlayLabel) overlayLabel.textContent = 'Loading…';
        if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.innerHTML = submitHtml;
        }
    }

    if (form && submitBtn) {
        form.addEventListener('submit', function (e) {
            var pid = document.getElementById('ledger_form_party_id');
            if (!pid || !String(pid.value || '').trim()) {
                e.preventDefault();
                alert('Please select a party first.');
                return;
            }
            showOverlay('Loading ledger…');
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span> Loading…';
        });
    }

    window.addEventListener('pageshow', function () {
        hideOverlay();
    });

    var pdfLink = document.getElementById('ledger-pdf-link');
    if (pdfLink) {
        pdfLink.addEventListener('click', function (e) {
            e.preventDefault();
            var href = pdfLink.getAttribute('href');
            if (!href || href === '#') {
                alert('Please select a party first.');
                return;
            }
            showOverlay('Preparing PDF…');
            fetch(href, { credentials: 'same-origin', headers: { Accept: 'application/pdf' } })
                .then(function (res) {
                    if (!res.ok) throw new Error('pdf');
                    var cd = res.headers.get('Content-Disposition');
                    var fname = 'daybook-ledger.pdf';
                    if (cd) {
                        var mStar = /filename\*\s*=\s*UTF-8''([^;\s]+)/i.exec(cd);
                        var mQuot = /filename\s*=\s*"([^"]+)"/i.exec(cd);
                        var mPlain = /filename\s*=\s*([^;\s]+)/i.exec(cd);
                        if (mStar) fname = decodeURIComponent(mStar[1].replace(/"/g, ''));
                        else if (mQuot) fname = mQuot[1];
                        else if (mPlain) fname = mPlain[1].replace(/"/g, '');
                    }
                    return res.blob().then(function (blob) {
                        return { blob: blob, fname: fname };
                    });
                })
                .then(function (o) {
                    var url = URL.createObjectURL(o.blob);
                    var a = document.createElement('a');
                    a.href = url;
                    a.download = o.fname;
                    document.body.appendChild(a);
                    a.click();
                    a.remove();
                    setTimeout(function () {
                        URL.revokeObjectURL(url);
                    }, 2000);
                })
                .catch(function () {})
                .finally(function () {
                    hideOverlay();
                });
        });
    }
})();

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
