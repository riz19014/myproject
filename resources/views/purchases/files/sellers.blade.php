@extends('layouts.app')

@section('title', 'Sellers — '.$purchase_file->file_name)

@section('content')
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
    <div>
        <h1 class="mb-1">Sellers</h1>
        <p class="text-muted small mb-0">
            File: <strong>{{ $purchase_file->file_name }}</strong>
            @if($purchase_file->file_date)
                · Date: <strong>{{ $purchase_file->file_date->format('d M Y') }}</strong>
            @endif
            · Project: <strong>{{ $purchase_file->project->name }}</strong>
        </p>
    </div>
    <a href="{{ route('purchase.files.index') }}" class="btn btn-outline-theme">Back to files</a>
</div>

@if($sellers->isNotEmpty())
    <div class="card card-theme mb-4">
        <div class="card-body">
            <h2 class="h6 mb-3">Sellers on this file</h2>
            <div class="table-responsive">
                <table class="table table-striped table-theme mb-0 align-middle">
                    <thead>
                        <tr>
                            <th style="width: 56px;">#</th>
                            <th>Party</th>
                            <th>Moza</th>
                            <th>Khasra</th>
                            <th>Area</th>
                            <th class="text-end">Rs / acre</th>
                            <th class="text-end">Line total</th>
                            <th style="width: 100px;"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($sellers as $seller)
                            <tr>
                                <td>{{ $seller->id }}</td>
                                <td class="fw-semibold small">{{ $seller->party?->name ?? '—' }}</td>
                                <td class="small">{{ $seller->moza ?: '—' }}</td>
                                <td class="small">{{ $seller->khasra ?: '—' }}</td>
                                <td class="small">{{ \App\Support\LandMeasure::formatAkmsLabelFromMarla((float) $seller->land_area_marla) }}</td>
                                <td class="text-end small">{{ number_format((float) $seller->amount_per_acre, 0) }}</td>
                                <td class="text-end fw-semibold">{{ number_format((float) $seller->line_total_rs, 0) }}</td>
                                <td class="text-end text-nowrap">
                                    <a href="{{ route('purchase.records.edit', $seller) }}" class="btn btn-sm btn-outline-theme" title="Edit">Edit</a>
                                    <form action="{{ route('purchase.files.sellers.destroy', [$purchase_file, $seller]) }}" method="post" class="d-inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="button" class="btn btn-sm btn-outline-danger btn-delete-confirm" data-title="Remove seller?" data-text="This seller row will be deleted from this file.">×</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endif

<div class="card card-theme">
    <div class="card-body">
        <h2 class="h6 mb-3">Add seller</h2>

        @if($parties->isEmpty())
            <div class="alert alert-warning small mb-0">
                No parties yet. <a href="{{ route('parties.index') }}">Add parties</a> first.
            </div>
        @else
            <form method="post" action="{{ route('purchase.files.sellers.store', $purchase_file) }}" id="file-sellers-form">
                @csrf
                <div id="file-seller-lines">
                    @foreach($lines as $i => $line)
                        @include('purchases.partials.line-row', ['line' => is_array($line) ? $line : [], 'parties' => $parties])
                    @endforeach
                </div>
                <div class="d-flex flex-wrap gap-2 align-items-center mb-4">
                    <button type="button" class="btn btn-outline-theme" id="add-file-seller-line">Add another seller</button>
                </div>
                <button type="submit" class="btn btn-pink">Save seller(s)</button>
            </form>
        @endif
    </div>
</div>

<template id="file-seller-line-template">
    @include('purchases.partials.line-row', ['line' => [], 'parties' => $parties])
</template>
@endsection

@push('scripts')
@include('purchases.partials.line-row-amount-hint-scripts')
<script>
(function() {
    var form = document.getElementById('file-sellers-form');
    var container = document.getElementById('file-seller-lines');
    var tpl = document.getElementById('file-seller-line-template');
    var addBtn = document.getElementById('add-file-seller-line');
    if (!form || !container || !tpl) return;

    function syncNames() {
        container.querySelectorAll('.purchase-line-block').forEach(function(block, i) {
            block.querySelectorAll('[data-line-field]').forEach(function(el) {
                var field = el.getAttribute('data-line-field');
                if (field) el.name = 'lines[' + i + '][' + field + ']';
            });
        });
    }

    function updateRemoveButtons() {
        var blocks = container.querySelectorAll('.purchase-line-block');
        var single = blocks.length <= 1;
        blocks.forEach(function(block) {
            var btn = block.querySelector('.js-remove-purchase-line');
            if (!btn) return;
            btn.disabled = single;
            btn.classList.toggle('opacity-25', single);
            btn.classList.toggle('pe-none', single);
        });
    }

    form.addEventListener('submit', syncNames);

    if (addBtn) {
        addBtn.addEventListener('click', function() {
            var html = tpl.innerHTML.trim();
            if (!html) return;
            container.insertAdjacentHTML('beforeend', html);
            syncNames();
            updateRemoveButtons();
            if (window.PurchaseLineAmountHints) PurchaseLineAmountHints.refresh(container);
        });
    }

    container.addEventListener('click', function(e) {
        if (!e.target.classList.contains('js-remove-purchase-line')) return;
        var block = e.target.closest('.purchase-line-block');
        if (!block || container.querySelectorAll('.purchase-line-block').length <= 1) return;
        block.remove();
        syncNames();
        updateRemoveButtons();
    });

    container.addEventListener('change', function(e) {
        var sel = e.target;
        if (!sel.classList || !sel.classList.contains('js-party-select')) return;
        var opt = sel.options[sel.selectedIndex];
        var name = opt && opt.value ? opt.text : '';
        var block = sel.closest('.purchase-line-block');
        var disp = block && block.querySelector('.js-party-name-display');
        if (disp) {
            disp.textContent = name || 'Party name appears here when you select a party…';
        }
    });

    syncNames();
    updateRemoveButtons();
    if (window.PurchaseLineAmountHints) PurchaseLineAmountHints.bind(container);
})();
</script>
@endpush
