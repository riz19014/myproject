@php
    $partyId = (int) ($line['party_id'] ?? 0);
    $partyName = $partyId ? ($parties->firstWhere('id', $partyId)?->name ?? '') : '';
    $areaAcreVal = (int) ($line['area_acre'] ?? 0);
    $areaKanalVal = (int) ($line['area_kanal'] ?? 0);
    $areaMarlaVal = (int) ($line['area_marla'] ?? 0);
    $areaSqftVal = (int) ($line['area_sqft'] ?? 0);
@endphp
@once
    @include('partials.party-sc-combo-styles')
    @include('purchases.partials.line-row-styles')
@endonce
<article class="purchase-line-block purchase-line-block--compact position-relative">
    <header class="purchase-line-block__head">
        <span class="purchase-line-block__badge">Seller</span>
        <button type="button" class="purchase-line-block__remove js-remove-purchase-line" aria-label="Remove this seller line">
            <i class="bi bi-trash" aria-hidden="true"></i>
            <span>Remove</span>
        </button>
    </header>

    <div class="purchase-line-block__body">
        <div class="purchase-line-block__section">
            <div class="purchase-line-block__section-title">Party &amp; location</div>
            <div class="row g-2 align-items-end">
                <div class="col-12 col-lg-5">
                    <label class="form-label">Party <span class="text-danger">*</span></label>
                    <div class="party-sc-combo js-party-picker">
                        <input type="hidden" data-line-field="party_id" value="{{ $partyId > 0 ? $partyId : '' }}" required>
                        <input type="text"
                            class="form-control form-control-theme js-party-picker-search"
                            value="{{ $partyName }}"
                            placeholder="Search party…"
                            autocomplete="off"
                            role="combobox"
                            aria-expanded="false"
                            aria-autocomplete="list">
                        <ul class="party-sc-listbox d-none js-party-picker-list" role="listbox" hidden></ul>
                    </div>
                </div>
                <div class="col-6 col-lg-3">
                    <label class="form-label">Moza</label>
                    <div class="party-sc-combo js-moza-suggest">
                        <input type="text"
                            class="form-control form-control-theme"
                            data-line-field="moza"
                            value="{{ $line['moza'] ?? '' }}"
                            maxlength="255"
                            placeholder="Search moza…"
                            autocomplete="off"
                            role="combobox"
                            aria-expanded="false"
                            aria-autocomplete="list">
                        <ul class="party-sc-listbox d-none js-moza-suggest-list" role="listbox" hidden></ul>
                    </div>
                </div>
                <div class="col-6 col-lg-3">
                    <label class="form-label">Khasra #</label>
                    <input type="text" class="form-control form-control-theme" data-line-field="khasra" value="{{ $line['khasra'] ?? '' }}" maxlength="255" autocomplete="off" placeholder="Optional">
                </div>
            </div>
            <div class="row g-2 mt-1">
                <div class="col-12 col-md-4">
                    <label class="form-label">Khewat #</label>
                    <input type="text" class="form-control form-control-theme" data-line-field="khewat_no" value="{{ $line['khewat_no'] ?? '' }}" maxlength="255" autocomplete="off" placeholder="Enter Khewat number">
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label">Khatooni #</label>
                    <input type="text" class="form-control form-control-theme" data-line-field="khatooni_no" value="{{ $line['khatooni_no'] ?? '' }}" maxlength="255" autocomplete="off" placeholder="Enter Khatooni number">
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label">Intiqal #</label>
                    <input type="text" class="form-control form-control-theme" data-line-field="intiqal_no" value="{{ $line['intiqal_no'] ?? '' }}" maxlength="255" autocomplete="off" placeholder="Enter Intiqal number">
                </div>
            </div>
        </div>

        <div class="purchase-line-block__section">
            <div class="purchase-line-block__section-title">Land area &amp; rate</div>
            <div class="purchase-line-akms-panel">
                <p class="purchase-line-akms-hint mb-0">Area <span class="text-danger">*</span> — fill at least one of Acre, Kanal, Marla, or Sq ft</p>
                <div class="purchase-line-akms-field">
                    <label class="form-label">Acre</label>
                    <input type="text" class="form-control form-control-theme js-line-integer-only js-line-area-part" data-line-field="area_acre" data-line-integer-zero="1" value="{{ $areaAcreVal > 0 ? $areaAcreVal : '' }}" placeholder="0" inputmode="numeric" autocomplete="off">
                </div>
                <div class="purchase-line-akms-field">
                    <label class="form-label">Kanal</label>
                    <input type="text" class="form-control form-control-theme js-line-integer-only js-line-area-part" data-line-field="area_kanal" data-line-integer-zero="1" value="{{ $areaKanalVal > 0 ? $areaKanalVal : '' }}" placeholder="0" inputmode="numeric" autocomplete="off">
                </div>
                <div class="purchase-line-akms-field">
                    <label class="form-label">Marla</label>
                    <input type="text" class="form-control form-control-theme js-line-integer-only js-line-area-part" data-line-field="area_marla" data-line-integer-zero="1" value="{{ $areaMarlaVal > 0 ? $areaMarlaVal : '' }}" placeholder="0" inputmode="numeric" autocomplete="off">
                </div>
                <div class="purchase-line-akms-field">
                    <label class="form-label">Sq ft</label>
                    <input type="text" class="form-control form-control-theme js-line-integer-only js-line-area-part" data-line-field="area_sqft" data-line-integer-zero="1" value="{{ $areaSqftVal > 0 ? $areaSqftVal : '' }}" placeholder="0" inputmode="numeric" autocomplete="off">
                </div>
                <div class="purchase-line-rate-field">
                    <label class="form-label">
                        Rs / acre <span class="text-danger">*</span>
                        <span class="js-amount-per-acre-hint d-none"></span>
                    </label>
                    <input type="text" class="form-control form-control-theme js-line-decimal-only" data-line-field="amount_per_acre" value="{{ isset($line['amount_per_acre']) && $line['amount_per_acre'] !== '' ? (float) $line['amount_per_acre'] : '' }}" inputmode="decimal" autocomplete="off" required placeholder="0">
                </div>
            </div>
        </div>
    </div>
</article>
