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

            <p class="text-muted small mb-3">Add one or more lines (party, Moza, Khasra, area, Rs per acre). Optionally group under a <strong>purchase file</strong> with dealers — <a href="{{ route('purchase.files.index', ['project' => $project->id]) }}">manage files</a>.</p>

            <div class="border rounded p-3 mb-4 bg-body-secondary bg-opacity-10">
                <div class="fw-semibold small mb-2">Put these lines under a purchase file <span class="text-muted fw-normal">(optional)</span></div>
                <p class="text-muted small mb-3 mb-md-0">Files live on the project (e.g. DHA). All lines saved in this batch share the same file. Leave both empty to save without a file, or pick an existing file, or create a new file name.</p>
                <div class="row g-3 align-items-end mt-1">
                    <div class="col-md-5">
                        <label for="purchase_file_id" class="form-label">Existing file</label>
                        <select class="form-select form-select-theme @error('purchase_file_id') is-invalid @enderror" id="purchase_file_id" name="purchase_file_id">
                            <option value="">— None —</option>
                            @foreach($project->purchaseFiles as $pf)
                                <option value="{{ $pf->id }}" @selected((string) old('purchase_file_id') === (string) $pf->id)>
                                    {{ $pf->file_name }}@if($pf->dealers->isNotEmpty()) ({{ $pf->dealers->pluck('name')->join(', ') }})@endif
                                </option>
                            @endforeach
                        </select>
                        @error('purchase_file_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4">
                        <label for="new_file_name" class="form-label">Or new file name</label>
                        <input type="text" class="form-control form-control-theme @error('new_file_name') is-invalid @enderror" id="new_file_name" name="new_file_name" value="{{ old('new_file_name') }}" maxlength="255" placeholder="e.g. 23 kanal 5 marla" autocomplete="off">
                        @error('new_file_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-3">
                        <button type="button" class="btn btn-outline-theme w-100" id="purchase-suggest-file-name" @if($parties->isEmpty()) disabled @endif>Suggest from areas</button>
                    </div>
                </div>
                
                <div class="mt-3">
                    <label class="form-label small mb-1">Dealers for <strong>new</strong> file</label>
                    <div class="border rounded p-2 bg-body" style="max-height: 140px; overflow-y: auto;">
                        @foreach($parties as $party)
                            <div class="form-check form-check-inline me-3">
                                <input class="form-check-input" type="checkbox" name="dealer_party_ids[]" value="{{ $party->id }}" id="line_dealer_{{ $party->id }}" @checked(in_array($party->id, old('dealer_party_ids', [])))>
                                <label class="form-check-label small" for="line_dealer_{{ $party->id }}">{{ $party->name }}</label>
                            </div>
                        @endforeach
                    </div>
                </div>
                <p class="text-muted small mt-2 mb-0">“Suggest from areas” adds the acre/kanal/marla/sq ft of every line below and fills <strong>Or new file name</strong> (e.g. <em>23 kanal 5 marla</em>). You can edit it before saving.</p>
            </div>

            @if($parties->isEmpty())
                <div class="alert alert-theme-danger py-2 small">No parties yet. Add parties first, then return here.</div>
            @else
                <script type="application/json" id="purchase_line_parties_json">@json($parties->map(fn ($p) => ['id' => $p->id, 'label' => $p->name])->values())</script>
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
@include('purchases.partials.line-row-amount-hint-scripts')
@include('purchases.partials.line-row-party-picker-scripts')
<script>
(function() {
    var suggestUrl = @json(route('purchase.records.suggest-file-name'));
    var csrf = document.querySelector('meta[name="csrf-token"]');
    var csrfToken = csrf ? csrf.getAttribute('content') : '';
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
        if (window.PurchaseLineAmountHints) PurchaseLineAmountHints.bind(container);
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
            if (window.PurchaseLinePartyPickers) PurchaseLinePartyPickers.refresh(container);
            if (window.PurchaseLineAmountHints) PurchaseLineAmountHints.refresh(container);
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

    function collectLinesPayload() {
        syncPurchaseLineFieldNames();
        var blocks = container.querySelectorAll('.purchase-line-block');
        var lines = [];
        blocks.forEach(function(block) {
            function val(field) {
                var el = block.querySelector('[data-line-field="' + field + '"]');
                return el ? parseInt(el.value || '0', 10) || 0 : 0;
            }
            lines.push({
                area_acre: val('area_acre'),
                area_kanal: val('area_kanal'),
                area_marla: val('area_marla'),
                area_sqft: val('area_sqft'),
            });
        });
        return { lines: lines };
    }

    var suggestBtn = document.getElementById('purchase-suggest-file-name');
    var newFileInput = document.getElementById('new_file_name');
    if (suggestBtn && newFileInput) {
        suggestBtn.addEventListener('click', function() {
            suggestBtn.disabled = true;
            fetch(suggestUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify(collectLinesPayload()),
            }).then(function(r) {
                return r.json().then(function(data) {
                    if (!r.ok) throw data;
                    if (data && data.name) newFileInput.value = data.name;
                });
            }).catch(function() {
                alert('Could not build a suggestion. Check each line has valid area numbers.');
            }).finally(function() {
                suggestBtn.disabled = false;
            });
        });
    }
})();
</script>
@endpush
