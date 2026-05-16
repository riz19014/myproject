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
                    <a href="{{ route('daybook.ledger') }}" class="btn btn-sm btn-outline-secondary d-inline-flex align-items-center gap-1">
                        <i class="bi bi-journal-bookmark" aria-hidden="true"></i>
                        <span>Ledger</span>
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
</script>
@endpush
