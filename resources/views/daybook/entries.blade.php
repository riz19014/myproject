@extends('layouts.app')

@section('title', 'Daybook entries')

@section('main_class', 'container-fluid px-3 py-3 daybook-entries-main')

@push('head')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/flatpickr.min.css">
@endpush

@section('content')
@include('daybook.partials.page-styles')
<div class="daybook-entries-page">
    <div class="card card-theme">
        <div class="card-body p-3 p-lg-4">
            <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-3">
                <div>
                    <h1 class="h4 mb-1">Daybook entries</h1>
                    <p class="text-muted small mb-0">Latest {{ $sidebarEntryRows->count() }} lines (newest first). Filter by date or search any field.</p>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <a href="{{ route('daybook.index') }}" class="btn btn-sm btn-outline-theme d-inline-flex align-items-center gap-1">
                        <i class="bi bi-journal-text" aria-hidden="true"></i>
                        <span>Open daybook</span>
                    </a>
                </div>
            </div>
            @include('daybook.partials.entries-global-list', [
                'sidebarEntryRows' => $sidebarEntryRows,
                'highlightDate' => $highlightDate,
                'dateFrom' => $dateFrom ?? null,
                'dateTo' => $dateTo ?? null,
            ])
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/flatpickr.min.js"></script>
<script>
(function () {
    var tbody = document.getElementById('daybook-sidebar-tbody');
    var input = document.getElementById('daybook-sidebar-search');
    var dateFromInput = document.getElementById('daybook-sidebar-date-from');
    var dateToInput = document.getElementById('daybook-sidebar-date-to');
    var dateClear = document.getElementById('daybook-sidebar-date-clear');
    var hint = document.getElementById('daybook-sidebar-filter-hint');
    if (!input && !dateFromInput && !dateToInput) return;
    var rows = tbody ? tbody.querySelectorAll('.daybook-sidebar-row') : [];
    var total = rows.length;
    var fromPicker = null;
    var toPicker = null;

    function tokens(q) {
        return q.toLowerCase().trim().split(/\s+/).filter(Boolean);
    }
    function matches(blob, q) {
        if (!q) return true;
        var ts = tokens(q);
        for (var i = 0; i < ts.length; i++) {
            if (blob.indexOf(ts[i]) === -1) return false;
        }
        return true;
    }
    function val(el) {
        return el ? (el.value || '').trim() : '';
    }
    function inRange(rowDate, from, to) {
        if (!from && !to) return true;
        if (!rowDate) return false;
        if (from && rowDate < from) return false;
        if (to && rowDate > to) return false;
        return true;
    }
    function syncUrlDates(from, to) {
        try {
            var url = new URL(window.location.href);
            url.searchParams.delete('date');
            if (from) url.searchParams.set('date_from', from);
            else url.searchParams.delete('date_from');
            if (to) url.searchParams.set('date_to', to);
            else url.searchParams.delete('date_to');
            window.history.replaceState({}, '', url.pathname + url.search + url.hash);
        } catch (e) {}
    }
    function formatAmount(n) {
        return Math.round(Math.abs(n)).toLocaleString('en-US');
    }
    function updateTotals(visibleCount, cashIn, cashOut, isFiltered) {
        var inEl = document.getElementById('daybook-entries-total-in');
        var outEl = document.getElementById('daybook-entries-total-out');
        var netEl = document.getElementById('daybook-entries-total-net');
        var noteEl = document.getElementById('daybook-entries-total-note');
        var rowEl = document.getElementById('daybook-entries-total-row');
        var net = cashIn - cashOut;
        if (inEl) inEl.textContent = '+' + formatAmount(cashIn);
        if (outEl) outEl.textContent = '−' + formatAmount(cashOut);
        if (netEl) {
            netEl.innerHTML = '<strong>Net ' + (net >= 0 ? '+' : '−') + formatAmount(net) + '</strong>';
        }
        if (noteEl) {
            noteEl.textContent = visibleCount
                ? ((isFiltered ? 'Filtered' : 'All') + ' · ' + visibleCount + ' line' + (visibleCount === 1 ? '' : 's'))
                : 'No matching lines';
        }
        if (rowEl) rowEl.classList.toggle('d-none', visibleCount === 0 && isFiltered === false && total === 0);
    }
    function runFilter() {
        var q = input ? (input.value || '') : '';
        var from = val(dateFromInput);
        var to = val(dateToInput);
        if (from && to && from > to) {
            var tmp = from;
            from = to;
            to = tmp;
        }
        var n = 0;
        var cashIn = 0;
        var cashOut = 0;
        rows.forEach(function (tr) {
            var span = tr.querySelector('.daybook-sidebar-search-blob');
            var blob = span ? span.textContent : '';
            var rowDate = tr.getAttribute('data-entry-date') || '';
            var ok = matches(blob, q) && inRange(rowDate, from, to);
            tr.classList.toggle('d-none', !ok);
            tr.classList.toggle('daybook-sidebar-row--selected-day', !!(from || to) && inRange(rowDate, from, to));
            if (ok) {
                n++;
                var amt = parseFloat(tr.getAttribute('data-amount') || '0') || 0;
                if (tr.getAttribute('data-cash-in') === '1') cashIn += amt;
                else cashOut += amt;
            }
        });
        var isFiltered = !!(q.trim() || from || to);
        updateTotals(n, cashIn, cashOut, isFiltered);
        if (hint) {
            var parts = [];
            if (from || to) {
                if (from && to && from === to) parts.push('date ' + from);
                else if (from && to) parts.push('date ' + from + ' to ' + to);
                else if (from) parts.push('from ' + from);
                else parts.push('to ' + to);
            }
            if (q.trim()) parts.push('search');
            hint.textContent = 'Showing ' + n + ' of ' + total + (parts.length ? ' (filtered by ' + parts.join(' + ') + ')' : '');
        }
    }
    function onDateChange() {
        syncUrlDates(val(dateFromInput), val(dateToInput));
        runFilter();
    }
    if (input) {
        input.addEventListener('input', runFilter);
        input.addEventListener('search', runFilter);
    }
    var fpOpts = {
        dateFormat: 'Y-m-d',
        allowInput: false,
        disableMobile: true,
        clickOpens: true,
        onChange: onDateChange
    };
    if (typeof flatpickr !== 'undefined') {
        if (dateFromInput) {
            fromPicker = flatpickr(dateFromInput, Object.assign({}, fpOpts, {
                defaultDate: dateFromInput.value || null
            }));
        }
        if (dateToInput) {
            toPicker = flatpickr(dateToInput, Object.assign({}, fpOpts, {
                defaultDate: dateToInput.value || null
            }));
        }
    } else {
        if (dateFromInput) dateFromInput.addEventListener('change', onDateChange);
        if (dateToInput) dateToInput.addEventListener('change', onDateChange);
    }
    if (dateClear) {
        dateClear.addEventListener('click', function () {
            if (fromPicker) fromPicker.clear();
            else if (dateFromInput) dateFromInput.value = '';
            if (toPicker) toPicker.clear();
            else if (dateToInput) dateToInput.value = '';
            syncUrlDates('', '');
            runFilter();
        });
    }
    runFilter();
})();

(function () {
    var modalEl = document.getElementById('daybookEntryModal');
    if (!modalEl || typeof bootstrap === 'undefined') return;
    if (modalEl.parentNode !== document.body) {
        document.body.appendChild(modalEl);
    }
    var modal = bootstrap.Modal.getOrCreateInstance(modalEl);

    function setRow(id, value) {
        var el = document.getElementById(id);
        if (!el) return;
        el.textContent = (value === null || value === undefined || value === '') ? '—' : value;
        var item = el.closest('.daybook-modal-item');
        if (item) item.classList.toggle('d-none', el.textContent === '—');
    }

    function openFromButton(btn) {
        var data;
        try {
            data = JSON.parse(btn.getAttribute('data-daybook-entry')) || {};
        } catch (e) {
            return;
        }

        var subtitle = document.getElementById('daybook-modal-subtitle');
        if (subtitle) subtitle.textContent = data.voucher ? ('Voucher ' + data.voucher) : ('Entry #' + (data.id || ''));

        setRow('daybook-modal-voucher', data.voucher);
        setRow('daybook-modal-date', data.date);
        setRow('daybook-modal-created-at', data.created_at);
        setRow('daybook-modal-type', data.type_label);
        setRow('daybook-modal-settlement', data.settlement === '—' ? '' : data.settlement);
        // Paid by temporarily hidden from daybook UI
        // setRow('daybook-modal-paid-by', (!data.paid_by || data.paid_by === '—') ? '' : data.paid_by);
        setRow('daybook-modal-description', data.description === '—' ? '' : data.description);
        setRow('daybook-modal-link', data.link_label);

        var projectText = data.project_name || '';
        if (projectText) {
            projectText = (data.project_is_dha ? '🟢 ' : '🟡 ') + projectText;
        }
        if (projectText && data.land_type) projectText += ' (' + data.land_type + ')';
        setRow('daybook-modal-project', projectText);

        setRow('daybook-modal-file', data.purchase_file);
        setRow('daybook-modal-sold-area', (!data.sold_area || data.sold_area === '—') ? '' : data.sold_area);

        var subcat = '';
        if (data.sub_category && data.sub_category !== '—') {
            subcat = (data.category ? data.category + ' · ' : '') + data.sub_category;
        }
        setRow('daybook-modal-subcat', subcat);

        var factoryWrap = document.getElementById('daybook-modal-factory');
        if (factoryWrap) {
            if (data.is_factory) {
                factoryWrap.classList.remove('d-none');
                setRow('daybook-modal-fsubcat', data.factory_sub_category === '—' ? '' : data.factory_sub_category);
                setRow('daybook-modal-unit', data.unit);
                setRow('daybook-modal-qty', data.quantity);
                setRow('daybook-modal-price', data.unit_price ? ('Rs ' + data.unit_price) : '');
            } else {
                factoryWrap.classList.add('d-none');
            }
        }

        var amountEl = document.getElementById('daybook-modal-amount');
        if (amountEl) {
            amountEl.textContent = data.amount || '—';
            amountEl.classList.remove('daybook-amount--in', 'daybook-amount--out');
            amountEl.classList.add(data.is_cash_in ? 'daybook-amount--in' : 'daybook-amount--out');
        }

        var openFull = document.getElementById('daybook-modal-open-full');
        if (openFull && data.url) openFull.setAttribute('href', data.url);

        modal.show();
    }

    document.querySelectorAll('.daybook-entries-view-btn[data-daybook-entry]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            openFromButton(btn);
        });
    });
})();
</script>
@endpush
