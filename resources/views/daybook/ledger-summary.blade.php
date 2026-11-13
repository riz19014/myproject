@extends('layouts.app')

@section('title', 'Ledger summary')

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
        .ledger-summary-projects tr.is-selected {
            background: #fff8f3;
        }
        .ledger-summary-projects tr.is-clickable {
            cursor: pointer;
        }
        .ledger-summary-projects tr.is-clickable:hover {
            background: #f8fafc;
        }
        .ledger-summary-projects tr.is-selected:hover {
            background: #fff1e6;
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
        .daybook-ledger-desc-tags {
            display: flex;
            flex-wrap: wrap;
            gap: 0.25rem;
            margin-top: 0.3rem;
        }
        .daybook-ledger-desc-tags .ledger-desc-tag {
            display: inline-flex;
            align-items: baseline;
            gap: 0.2rem;
            max-width: 100%;
            padding: 0.1rem 0.4rem;
            border: 1px solid #e2e8f0;
            border-radius: 0.25rem;
            background: #f8fafc;
            color: #475569;
            font-size: 0.7rem;
            line-height: 1.35;
            white-space: normal;
        }
        .daybook-ledger-desc-tags .ledger-desc-tag__label {
            font-weight: 600;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.02em;
            font-size: 0.62rem;
        }
        .ledger-summary-project-cell {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 0.35rem 0.55rem;
        }
        .ledger-summary-status {
            display: inline-block;
            font-size: 0.7rem;
            font-weight: 600;
            line-height: 1.3;
            padding: 0.15rem 0.45rem;
            border-radius: 0.35rem;
            border: 1px solid rgba(15, 23, 42, 0.14);
            white-space: nowrap;
        }
        .ledger-summary-status.is-in_progress {
            border-color: rgba(37, 99, 235, 0.35);
            background: #eff6ff;
            color: #1d4ed8;
        }
        .ledger-summary-status.is-under_review {
            border-color: rgba(217, 119, 6, 0.4);
            background: #fffbeb;
            color: #b45309;
        }
        .ledger-summary-status.is-completed {
            border-color: rgba(22, 163, 74, 0.35);
            background: #f0fdf4;
            color: #15803d;
        }
    </style>
    @include('daybook.partials.entry-detail-modal-styles')
    <style>
        @media print {
            .no-print { display: none !important; }
            .app-sidebar, .app-topbar { display: none !important; }
            .app-main { margin: 0 !important; padding: 0 !important; }
        }
    </style>
@endpush

@section('content')
@php
    $summary_ready = $summary_ready ?? false;
    $ledger_ready = $ledger_ready ?? false;
    $ledger_from_input = $ledger_from_input ?? '';
    $ledger_to_input = $ledger_to_input ?? '';
    $projectSummaries = $projectSummaries ?? [];
    $baseQuery = array_filter([
        '_summary' => 1,
        'party_id' => $party_id ?: null,
        'from' => $ledger_from_input !== '' ? $ledger_from_input : null,
        'to' => $ledger_to_input !== '' ? $ledger_to_input : null,
    ], fn ($v) => $v !== null && $v !== '');
    $pdfAllQuery = $baseQuery;
    unset($pdfAllQuery['_summary']);
    $pdfOneQuery = $pdfAllQuery;
    if ($ledger_ready && $project_id !== null) {
        $pdfOneQuery['project_id'] = $project_id;
    }
@endphp
<div class="no-print mb-3">
    <div class="d-flex flex-wrap align-items-end gap-3">
        <form method="get" action="{{ route('daybook.ledger-summary') }}" class="d-flex flex-wrap align-items-end gap-3 flex-grow-1" id="ledger-summary-filter-form">
            <input type="hidden" name="_summary" value="1">
            <div style="min-width: min(100%, 220px); max-width: 320px;">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-1">
                    <label class="form-label small text-muted mb-0" for="ledger_summary_party_search">Party</label>
                    <a href="{{ route('daybook.ledger') }}" class="small fw-semibold text-decoration-none">Full ledger</a>
                </div>
                <div class="daybook-form-combo @error('party_id') is-invalid @enderror">
                    <input type="hidden" name="party_id" id="ledger_summary_party_id" value="{{ old('party_id', $party_id ?: '') }}">
                    <input
                        type="text"
                        class="form-control form-control-theme @error('party_id') is-invalid @enderror"
                        id="ledger_summary_party_search"
                        placeholder="Search party…"
                        autocomplete="off"
                        role="combobox"
                        aria-expanded="false"
                        aria-controls="ledger_summary_party_listbox"
                        aria-autocomplete="list"
                    >
                    <ul class="daybook-form-combo-list d-none" id="ledger_summary_party_listbox" role="listbox" hidden></ul>
                </div>
                @error('party_id')
                    <div class="invalid-feedback d-block">{{ $message }}</div>
                @enderror
            </div>
            <div>
                <label for="ledger-summary-from" class="form-label small text-muted mb-0">From <span class="fw-normal">(optional)</span></label>
                <input type="text" name="from" id="ledger-summary-from" class="form-control form-control-theme" value="{{ old('from', $ledger_from_input) }}" placeholder="All history" autocomplete="off">
            </div>
            <div>
                <label for="ledger-summary-to" class="form-label small text-muted mb-0">To <span class="fw-normal">(optional)</span></label>
                <input type="text" name="to" id="ledger-summary-to" class="form-control form-control-theme" value="{{ old('to', $ledger_to_input) }}" placeholder="Today" autocomplete="off">
            </div>
            <button type="submit" class="btn btn-theme">Show summary</button>
        </form>
        <div class="d-flex flex-wrap gap-2">
            @if($summary_ready && count($projectSummaries))
                <a class="btn btn-outline-theme" id="ledger-summary-pdf-all" href="{{ route('daybook.ledger-summary.pdf', $pdfAllQuery) }}">Print all projects</a>
            @endif
            @if($ledger_ready)
                <a class="btn btn-outline-theme" id="ledger-summary-pdf-one" href="{{ route('daybook.ledger-summary.pdf', $pdfOneQuery) }}">Print this project</a>
                <button type="button" class="btn btn-outline-theme" onclick="window.print()">Print view</button>
            @endif
            <a class="btn btn-outline-theme" href="{{ route('daybook.index') }}">Daybook</a>
        </div>
    </div>
    <script type="application/json" id="ledger-summary-parties-json">@json($parties->map(function ($p) {
        return ['id' => $p->id, 'label' => $p->name];
    })->values())</script>
</div>

<div id="ledger-loading-overlay" class="ledger-loading-overlay d-none no-print" role="status" aria-live="polite" aria-busy="true" aria-hidden="true">
    <div class="spinner-border text-light" style="width: 2.75rem; height: 2.75rem;" aria-hidden="true"></div>
    <p class="ledger-loading-overlay__label" id="ledger-loading-overlay-label">Loading…</p>
</div>

<div class="card card-theme mb-4">
    <div class="card-body">
        <h1 class="h5 mb-2">Ledger summary</h1>
        @if($summary_ready)
            <p class="text-muted small mb-3">
                {{ $from->format('j M Y') }} — {{ $to->format('j M Y') }}
                · <strong>{{ $selectedParty->name }}</strong>
                · click a project to show/hide its ledger below
            </p>
        @else
            <p class="text-muted small mb-0">Choose a party, then Show summary to list projects.</p>
        @endif

        <div class="table-responsive">
            <table class="table table-bordered table-sm mb-0 align-middle ledger-summary-projects">
                <thead class="text-nowrap">
                    <tr class="table-dark">
                        <th scope="col">Project</th>
                        <th scope="col" class="text-end" style="width:10%">Entries</th>
                        <th scope="col" class="text-end" style="width:14%">Payment in</th>
                        <th scope="col" class="text-end" style="width:14%">Payment out</th>
                        <th scope="col" class="text-end" style="width:14%">Net</th>
                        <th scope="col" class="no-print" style="width:12%">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($projectSummaries as $row)
                        @php
                            $openQuery = $baseQuery + ['project_id' => $row['project_id']];
                            $isSelected = $ledger_ready && (int) $project_id === (int) $row['project_id'];
                            // Toggle: selected project click hides ledger (summary without project_id).
                            $toggleHref = $isSelected
                                ? route('daybook.ledger-summary', $baseQuery)
                                : route('daybook.ledger-summary', $openQuery);
                        @endphp
                        <tr
                            class="is-clickable {{ $isSelected ? 'is-selected' : '' }}"
                            data-href="{{ $toggleHref }}"
                            data-toggle-action="{{ $isSelected ? 'hide' : 'show' }}"
                            tabindex="0"
                            role="button"
                            aria-expanded="{{ $isSelected ? 'true' : 'false' }}"
                            aria-label="{{ $isSelected ? 'Hide' : 'Show' }} ledger for {{ $row['project_name'] }}"
                        >
                            <td>
                                @if((int) $row['project_id'] === 0)
                                    <span class="text-muted">{{ $row['project_name'] }}</span>
                                @else
                                    <div class="ledger-summary-project-cell">
                                        <x-project-name :name="$row['project_name']" :is-dha="$row['project_is_dha']" />
                                        @if(($row['project_status_label'] ?? '') !== '')
                                            <span class="ledger-summary-status is-{{ $row['project_status'] }}">{{ $row['project_status_label'] }}</span>
                                        @endif
                                    </div>
                                    @if($row['land_type'] !== '')
                                        <span class="d-block small text-muted">{{ $row['land_type'] }}</span>
                                    @endif
                                @endif
                            </td>
                            <td class="text-end font-monospace">{{ $row['entries_count'] }}</td>
                            <td class="text-end font-monospace text-success">{{ $row['cash_in'] > 0 ? 'Rs '.number_format($row['cash_in'], 0) : '—' }}</td>
                            <td class="text-end font-monospace text-danger">{{ $row['cash_out'] > 0 ? 'Rs '.number_format($row['cash_out'], 0) : '—' }}</td>
                            <td class="text-end font-monospace">Rs {{ number_format($row['net'], 0) }}</td>
                            <td class="no-print">
                                <a class="btn btn-sm {{ $isSelected ? 'btn-outline-theme' : 'btn-pink' }}" href="{{ $toggleHref }}">{{ $isSelected ? 'Hide' : 'Open' }}</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-muted text-center py-4">
                                @if(!$summary_ready)
                                    Choose a party, then <strong>Show summary</strong>.
                                @else
                                    No projects found for this party in the selected range.
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@if($ledger_ready && $selectedProject)
<div class="card card-theme daybook-ledger-print mb-4" id="ledger-summary-detail">
    <div class="card-body">
        <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-2">
            <div>
                <h2 class="h5 mb-1 d-flex flex-wrap align-items-center gap-2">
                    <span>
                        Project ledger —
                        @if((int) $selectedProject['project_id'] === 0)
                            No project
                        @else
                            <x-project-name :name="$selectedProject['project_name']" :is-dha="$selectedProject['project_is_dha']" />
                        @endif
                    </span>
                    @if((int) $selectedProject['project_id'] !== 0 && ($selectedProject['project_status_label'] ?? '') !== '')
                        <span class="ledger-summary-status is-{{ $selectedProject['project_status'] }}">{{ $selectedProject['project_status_label'] }}</span>
                    @endif
                </h2>
                <p class="text-muted small mb-0">
                    {{ $from->format('j M Y') }} — {{ $to->format('j M Y') }}
                    · {{ $selectedParty->name }}
                </p>
            </div>
        </div>
        @if($grandCashIn > 0 || $grandCashOut > 0)
            <p class="small text-muted mb-3">
                @if($grandCashIn > 0)
                    <span class="text-success">Payment in:</span> Rs {{ number_format($grandCashIn, 0) }}
                @endif
                @if($grandCashIn > 0 && $grandCashOut > 0)
                    <span class="mx-2">·</span>
                @endif
                @if($grandCashOut > 0)
                    <span class="text-danger">Payment out:</span> Rs {{ number_format($grandCashOut, 0) }}
                @endif
            </p>
        @endif

        <div class="table-responsive">
            <table class="table table-bordered table-sm mb-0 align-middle daybook-ledger-statement">
                <thead class="text-nowrap">
                    <tr class="table-dark">
                        <th scope="col" style="width:9%">Date</th>
                        <th scope="col" style="width:9%">Voucher</th>
                        <th scope="col" style="width:10%">Payment</th>
                        <th scope="col" style="width:16%">Settlement</th>
                        <th scope="col">Description</th>
                        <th scope="col" class="text-end" style="width:11%">Amount (Rs.)</th>
                        <th scope="col" class="text-end" style="width:11%">Balance (Rs.)</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($ledgerRows as $r)
                        <tr>
                            <td>{{ $r['date'] }}</td>
                            <td class="text-nowrap">
                                @if(!empty($r['voucher']) && !empty($r['entry_modal']))
                                    <button
                                        type="button"
                                        class="daybook-voucher-chip font-monospace"
                                        data-daybook-entry="{{ json_encode($r['entry_modal'], JSON_HEX_APOS | JSON_HEX_QUOT) }}"
                                        title="View voucher details"
                                    >{{ $r['voucher'] }}</button>
                                @elseif(!empty($r['voucher']))
                                    <span class="daybook-voucher-chip font-monospace">{{ $r['voucher'] }}</span>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td>{{ $r['payment'] }}</td>
                            <td class="small">{{ $r['settlement'] ?? '—' }}</td>
                            <td>
                                <div>{{ $r['description'] }}</div>
                                @if(!empty($r['description_tags']))
                                    <div class="daybook-ledger-desc-tags">
                                        @foreach($r['description_tags'] as $tag)
                                            <span class="ledger-desc-tag">
                                                <span class="ledger-desc-tag__label">{{ $tag['label'] }}</span>
                                                <span>{{ $tag['value'] }}</span>
                                            </span>
                                        @endforeach
                                    </div>
                                @endif
                            </td>
                            <td class="text-end font-monospace">{{ $r['amount'] }}</td>
                            <td class="text-end font-monospace">{{ $r['balance_display'] }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-muted text-center py-4">No entries for this project.</td>
                        </tr>
                    @endforelse
                </tbody>
                @if(count($ledgerFooter))
                <tfoot>
                    <tr class="border-top border-2">
                        <td colspan="5" class="border-end-0 bg-transparent"></td>
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
@endif

@include('daybook.partials.entry-detail-modal')
@endsection

@push('scripts')
@include('daybook.partials.entry-detail-modal-scripts')
<script src="https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/flatpickr.min.js"></script>
<script>
(function () {
    var overlay = document.getElementById('ledger-loading-overlay');
    var overlayLabel = document.getElementById('ledger-loading-overlay-label');
    var form = document.getElementById('ledger-summary-filter-form');
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
        form.addEventListener('submit', function () {
            showOverlay('Loading summary…');
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span> Loading…';
        });
    }

    window.addEventListener('pageshow', function () {
        hideOverlay();
    });

    document.querySelectorAll('.ledger-summary-projects tr[data-href]').forEach(function (tr) {
        tr.addEventListener('click', function (e) {
            if (e.target.closest('a, button')) return;
            var href = tr.getAttribute('data-href');
            if (!href) return;
            var action = tr.getAttribute('data-toggle-action') || 'show';
            showOverlay(action === 'hide' ? 'Hiding project ledger…' : 'Loading project ledger…');
            window.location.href = href;
        });
        tr.addEventListener('keydown', function (e) {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                tr.click();
            }
        });
    });

    function bindPdfDownload(linkId, busyText) {
        var pdfLink = document.getElementById(linkId);
        if (!pdfLink) return;
        pdfLink.addEventListener('click', function (e) {
            e.preventDefault();
            var href = pdfLink.getAttribute('href');
            if (!href || href === '#') return;
            showOverlay(busyText);
            fetch(href, { credentials: 'same-origin', headers: { Accept: 'application/pdf' } })
                .then(function (res) {
                    if (!res.ok) throw new Error('pdf');
                    var cd = res.headers.get('Content-Disposition');
                    var fname = 'ledger-summary.pdf';
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
                    setTimeout(function () { URL.revokeObjectURL(url); }, 2000);
                })
                .catch(function () {})
                .finally(function () { hideOverlay(); });
        });
    }

    bindPdfDownload('ledger-summary-pdf-all', 'Preparing all-projects PDF…');
    bindPdfDownload('ledger-summary-pdf-one', 'Preparing project PDF…');

    @if($ledger_ready)
    var detail = document.getElementById('ledger-summary-detail');
    if (detail) {
        detail.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
    @endif
})();

(function () {
    if (typeof flatpickr === 'undefined') return;
    var opts = { dateFormat: 'Y-m-d', allowInput: true, disableMobile: true, clickOpens: true };
    var fromEl = document.getElementById('ledger-summary-from');
    var toEl = document.getElementById('ledger-summary-to');
    if (fromEl) flatpickr(fromEl, opts);
    if (toEl) flatpickr(toEl, opts);
})();

(function () {
    var partyHidden = document.getElementById('ledger_summary_party_id');
    var partySearch = document.getElementById('ledger_summary_party_search');
    var partyList = document.getElementById('ledger_summary_party_listbox');
    var formEl = document.getElementById('ledger-summary-filter-form');
    var partyWrap = partySearch ? partySearch.closest('.daybook-form-combo') : null;
    var partyJsonEl = document.getElementById('ledger-summary-parties-json');
    if (!partyHidden || !partySearch || !partyList) return;

    var formPartyRows = [];
    if (partyJsonEl) {
        try { formPartyRows = JSON.parse(partyJsonEl.textContent) || []; } catch (e) { formPartyRows = []; }
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
            btn.addEventListener('mousedown', function (e) { e.preventDefault(); });
            btn.addEventListener('click', function () {
                partyHidden.value = String(row.id);
                partySearch.value = row.label;
                hidePartyFormList();
                if (formEl && typeof formEl.requestSubmit === 'function') formEl.requestSubmit();
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

    partySearch.addEventListener('focus', openFilteredPartyFormList);
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
