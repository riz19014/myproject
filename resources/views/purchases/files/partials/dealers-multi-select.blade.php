{{-- Expects $parties collection; optional $selectedDealerIds array of party ids --}}
@php
    $selectedDealerIds = $selectedDealerIds ?? old('dealer_party_ids', []);
    $selectedDealerIds = array_map('intval', (array) $selectedDealerIds);
@endphp
@include('partials.party-sc-combo-styles')
<style>
    .pf-dealers-multi .pf-dealer-chip {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        padding: 0.25rem 0.55rem 0.25rem 0.65rem;
        font-size: 0.875rem;
        border-radius: 999px;
        background: rgba(249, 115, 22, 0.12);
        border: 1px solid rgba(249, 115, 22, 0.35);
        color: var(--text-dark, #0f172a);
    }
    .pf-dealers-multi .pf-dealer-chip button {
        border: 0;
        background: transparent;
        padding: 0;
        line-height: 1;
        color: #64748b;
        font-size: 1.1rem;
    }
    .pf-dealers-multi .pf-dealer-chip button:hover {
        color: #b91c1c;
    }
</style>
<div class="pf-dealers-multi @error('dealer_party_ids') is-invalid @enderror" id="purchase_file_dealers_wrap">
    <div id="purchase_file_dealers_chips" class="d-flex flex-wrap gap-2 mb-2"></div>
    <div class="party-sc-combo">
        <input type="text"
            class="form-control form-control-theme"
            id="purchase_file_dealers_search"
            placeholder="Search dealer party…"
            autocomplete="off"
            role="combobox"
            aria-expanded="false"
            aria-controls="purchase_file_dealers_listbox"
            aria-autocomplete="list">
        <ul class="party-sc-listbox d-none" id="purchase_file_dealers_listbox" role="listbox" hidden></ul>
    </div>
    <div id="purchase_file_dealers_hidden"></div>
    <p id="purchase_file_dealers_hint" class="text-muted small mb-0 mt-2">Type to search and click a name to add.</p>
</div>
<script type="application/json" id="purchase_file_dealers_json">@json($parties->map(function ($p) {
    return ['id' => $p->id, 'label' => $p->name];
})->values())</script>
<script type="application/json" id="purchase_file_dealers_selected_json">@json($selectedDealerIds)</script>
