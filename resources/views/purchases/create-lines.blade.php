@extends('layouts.app')

@section('title', 'Add purchase — '.$project->name)

@section('content')
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
    <div>
        <h1 class="mb-1">Add purchase</h1>
        <div class="text-muted small">Project: <strong>{{ $project->name }}</strong></div>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <a href="{{ route('purchase.records.create') }}" class="btn btn-outline-theme">Change project</a>
        <a href="{{ route('purchase.index') }}" class="btn btn-outline-theme">Back to Purchase</a>
    </div>
</div>

<div class="card card-theme">
    <div class="card-body">
        <form method="post" action="{{ route('purchase.records.store') }}" id="purchase-lines-form">
            @csrf
            <input type="hidden" name="project_id" value="{{ $project->id }}">

            <p class="text-muted small mb-3">Add one or more lines. Each line is one party slice: Moza, Khasra, land area, and Rs per acre. Line total is computed from area (in marla) × rate per acre.</p>

            @if($parties->isEmpty())
                <div class="alert alert-theme-danger py-2 small">No parties yet. Add parties first, then return here.</div>
            @endif

            <div id="purchase-lines">
                @foreach($lines as $i => $line)
                    @include('purchases.partials.line-row', ['line' => is_array($line) ? $line : [], 'parties' => $parties])
                @endforeach
            </div>

            <div class="d-flex flex-wrap gap-2 align-items-center mb-4">
                <button type="button" class="btn btn-outline-theme" id="add-purchase-line" @if($parties->isEmpty()) disabled @endif>Add line</button>
            </div>

            <button type="submit" class="btn btn-pink" @if($parties->isEmpty()) disabled @endif>Save all lines</button>
        </form>
    </div>
</div>

<template id="purchase-line-template">
    @include('purchases.partials.line-row', ['line' => [], 'parties' => $parties])
</template>
@endsection

@push('scripts')
<script>
(function() {
    var form = document.getElementById('purchase-lines-form');
    var container = document.getElementById('purchase-lines');
    var tpl = document.getElementById('purchase-line-template');
    var addBtn = document.getElementById('add-purchase-line');
    if (!form || !container || !tpl) return;

    function syncPurchaseLineFieldNames() {
        container.querySelectorAll('.purchase-line-block').forEach(function(block, i) {
            block.querySelectorAll('[data-line-field]').forEach(function(el) {
                var field = el.getAttribute('data-line-field');
                if (field) {
                    el.name = 'lines[' + i + '][' + field + ']';
                }
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

    document.addEventListener('DOMContentLoaded', function() {
        syncPurchaseLineFieldNames();
        updateRemoveButtons();
    });

    form.addEventListener('submit', function() {
        syncPurchaseLineFieldNames();
    });

    if (addBtn) {
        addBtn.addEventListener('click', function() {
            var html = tpl.innerHTML.trim();
            if (!html) return;
            container.insertAdjacentHTML('beforeend', html);
            syncPurchaseLineFieldNames();
            updateRemoveButtons();
        });
    }

    container.addEventListener('click', function(e) {
        var t = e.target;
        if (!t.classList.contains('js-remove-purchase-line')) return;
        var block = t.closest('.purchase-line-block');
        if (!block || container.querySelectorAll('.purchase-line-block').length <= 1) return;
        block.remove();
        syncPurchaseLineFieldNames();
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
})();
</script>
@endpush
