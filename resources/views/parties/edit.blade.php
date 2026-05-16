@extends('layouts.app')

@section('title', 'Edit party')

@section('content')
@include('partials.party-sc-combo-styles')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1>Edit party</h1>
    <a href="{{ route('parties.index') }}" class="btn btn-outline-theme">Back to list</a>
</div>

<div class="card card-theme">
    <div class="card-body">
        <form action="{{ route('parties.update', $party) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="mb-3">
                <label for="name" class="form-label">Name <span class="text-danger">*</span></label>
                <input type="text" class="form-control form-control-theme @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $party->name) }}" required maxlength="255">
                @error('name')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3 party-sc-combo" id="party_form_sc_wrap">
                <label class="form-label" for="party_form_sub_search">Party sub category <span class="text-danger">*</span></label>
                <input type="hidden" name="sub_category_id" id="party_form_sub_category_id" value="{{ old('sub_category_id', $party->sub_category_id) }}" required>
                <input type="text"
                    class="form-control form-control-theme @error('sub_category_id') is-invalid @enderror"
                    id="party_form_sub_search"
                    placeholder="Search sub category…"
                    autocomplete="off"
                    role="combobox"
                    aria-expanded="false"
                    aria-controls="party_form_sc_listbox"
                    aria-autocomplete="list">
                <ul class="party-sc-listbox d-none" id="party_form_sc_listbox" role="listbox" hidden></ul>
                @error('sub_category_id')
                    <div class="invalid-feedback d-block">{{ $message }}</div>
                @enderror
            </div>
            <script type="application/json" id="party_form_sub_json">@json($partySubCategories->map(function ($sc) {
                return ['id' => $sc->id, 'label' => ($sc->category?->name ?? '—').' — '.$sc->name];
            })->values())</script>

            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label for="phone" class="form-label">Phone</label>
                    <input type="text" class="form-control form-control-theme @error('phone') is-invalid @enderror" id="phone" name="phone" value="{{ old('phone', $party->phone) }}" maxlength="11" inputmode="numeric" pattern="\d{11}" placeholder="11 digits e.g. 03001234567">
                    @error('phone')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-md-6">
                    <label for="cnic" class="form-label">CNIC</label>
                    <input type="text" class="form-control form-control-theme @error('cnic') is-invalid @enderror" id="cnic" name="cnic" value="{{ \App\Support\CnicFormat::display(old('cnic', $party->cnic)) }}" maxlength="15" inputmode="numeric" placeholder="23012-2321373-1">
                    @error('cnic')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="mb-3">
                <label for="address" class="form-label">Address</label>
                <textarea class="form-control form-control-theme @error('address') is-invalid @enderror" id="address" name="address" rows="2" maxlength="2000">{{ old('address', $party->address) }}</textarea>
                @error('address')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                <label for="opening_balance" class="form-label">Opening balance (Rs)</label>
                <input type="number" class="form-control form-control-theme @error('opening_balance') is-invalid @enderror" id="opening_balance" name="opening_balance" value="{{ old('opening_balance', number_format((float) $party->opening_balance, 2, '.', '')) }}" step="0.01">
                @error('opening_balance')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <button type="submit" class="btn btn-pink">Update</button>
        </form>
    </div>
</div>
@include('partials.party-form-field-scripts')
<script>
(function () {
    var PF = window.PartyFormFields;
    if (!PF) return;
    var subJsonEl = document.getElementById('party_form_sub_json');
    var rows = [];
    if (subJsonEl) {
        try { rows = JSON.parse(subJsonEl.textContent) || []; } catch (e) { rows = []; }
    }
    PF.initSubCategoryCombo({
        wrap: document.getElementById('party_form_sc_wrap'),
        hidden: document.getElementById('party_form_sub_category_id'),
        search: document.getElementById('party_form_sub_search'),
        list: document.getElementById('party_form_sc_listbox'),
        rows: rows,
        initialId: document.getElementById('party_form_sub_category_id').value
    });
    PF.bindCnicInput(document.getElementById('cnic'));
    PF.bindPhoneInput(document.getElementById('phone'), 11);
})();
</script>
@endsection
