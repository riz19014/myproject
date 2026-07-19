@extends('layouts.app')

@section('title', 'Daybook entries')

@section('main_class', 'container-fluid px-3 py-4')

@section('content')
@include('daybook.partials.page-styles')
<div class="daybook-entries-page">
    <div class="card card-theme">
        <div class="card-body p-3 p-lg-4">
            <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-3">
                <div>
                    <h1 class="h4 mb-1">Daybook entries</h1>
                    <p class="text-muted small mb-0">Latest {{ $sidebarEntryRows->count() }} lines (newest first). Use search to narrow by any field.</p>
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
            ])
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    var tbody = document.getElementById('daybook-sidebar-tbody');
    var input = document.getElementById('daybook-sidebar-search');
    var hint = document.getElementById('daybook-sidebar-filter-hint');
    if (!input) return;
    var rows = tbody ? tbody.querySelectorAll('.daybook-sidebar-row') : [];
    var total = rows.length;
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
    function runFilter() {
        var q = input.value || '';
        var n = 0;
        rows.forEach(function (tr) {
            var span = tr.querySelector('.daybook-sidebar-search-blob');
            var blob = span ? span.textContent : '';
            var ok = matches(blob, q);
            tr.classList.toggle('d-none', !ok);
            if (ok) n++;
        });
        if (hint) hint.textContent = 'Showing ' + n + ' of ' + total + (q.trim() ? ' (filtered)' : '');
    }
    input.addEventListener('input', runFilter);
    input.addEventListener('search', runFilter);
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
        setRow('daybook-modal-type', data.type_label);
        setRow('daybook-modal-settlement', data.settlement === '—' ? '' : data.settlement);
        // Paid by temporarily hidden from daybook UI
        // setRow('daybook-modal-paid-by', (!data.paid_by || data.paid_by === '—') ? '' : data.paid_by);
        setRow('daybook-modal-description', data.description === '—' ? '' : data.description);
        setRow('daybook-modal-link', data.link_label);

        var projectText = data.project_name || '';
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
