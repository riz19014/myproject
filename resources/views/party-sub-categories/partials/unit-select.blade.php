@php
    $units = config('construction_units', []);
    $selectedUnit = old('unit', $selectedUnit ?? '');
@endphp

<div class="mb-3">
    <label for="party_sub_unit_search" class="form-label">Unit <span class="text-muted fw-normal">(optional)</span></label>
    <div class="daybook-form-combo @error('unit') is-invalid @enderror" id="party_sub_unit_wrap">
        <input type="hidden" name="unit" id="party_sub_unit" value="{{ $selectedUnit }}">
        <input
            type="text"
            id="party_sub_unit_search"
            class="form-control form-control-theme"
            value="{{ $selectedUnit }}"
            placeholder="Search unit, e.g. bag, cft, ton"
            autocomplete="off"
            role="combobox"
            aria-expanded="false"
            aria-controls="party_sub_unit_listbox"
        >
        <ul class="daybook-form-combo-list d-none" id="party_sub_unit_listbox" role="listbox" hidden></ul>
    </div>
    @error('unit')
        <div class="invalid-feedback d-block">{{ $message }}</div>
    @enderror
</div>

<script type="application/json" id="party_sub_unit_json">@json($units)</script>

@once
    @push('scripts')
        @include('partials.party-form-field-scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                var PF = window.PartyFormFields;
                var jsonEl = document.getElementById('party_sub_unit_json');
                var hidden = document.getElementById('party_sub_unit');
                var search = document.getElementById('party_sub_unit_search');
                var list = document.getElementById('party_sub_unit_listbox');
                var wrap = document.getElementById('party_sub_unit_wrap');
                if (!PF || !jsonEl || !hidden || !search || !list || !wrap) return;

                var units = [];
                try {
                    units = JSON.parse(jsonEl.textContent) || [];
                } catch (e) {
                    units = [];
                }

                PF.initStringCombo({
                    wrap: wrap,
                    hidden: hidden,
                    search: search,
                    list: list,
                    values: units,
                    initialValue: hidden.value || '',
                    emptyLabel: 'No unit',
                    noMatchText: 'No units match.',
                    emptyText: 'No units configured.'
                });
            });
        </script>
    @endpush
@endonce
