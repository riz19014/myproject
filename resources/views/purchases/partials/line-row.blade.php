@php
    $partyId = (int) ($line['party_id'] ?? 0);
    $partyName = $partyId ? ($parties->firstWhere('id', $partyId)?->name ?? '') : '';
@endphp
<div class="purchase-line-block border rounded p-3 mb-3 bg-body-secondary bg-opacity-10 position-relative">
    <button type="button" class="btn btn-sm btn-link text-danger js-remove-purchase-line position-absolute top-0 end-0 mt-1 me-1" aria-label="Remove line">Remove</button>

    <div class="row g-3 align-items-end">
        <div class="col-md-6 col-lg-4">
            <label class="form-label">Party <span class="text-danger">*</span></label>
            <select class="form-select form-select-theme js-party-select" data-line-field="party_id" required>
                <option value="" @if($partyId === 0) selected @endif disabled>— Select party —</option>
                @foreach($parties as $party)
                    <option value="{{ $party->id }}" @selected($partyId === (int) $party->id)>{{ $party->name }}</option>
                @endforeach
            </select>
            <div class="js-party-name-display small text-muted mt-1">{{ $partyName !== '' ? $partyName : 'Party name appears here when you select a party…' }}</div>
        </div>
        <div class="col-6 col-md-3 col-lg-2">
            <label class="form-label">Moza</label>
            <input type="text" class="form-control form-control-theme" data-line-field="moza" value="{{ $line['moza'] ?? '' }}" maxlength="255" autocomplete="off">
        </div>
        <div class="col-6 col-md-3 col-lg-2">
            <label class="form-label">Khasra #</label>
            <input type="text" class="form-control form-control-theme" data-line-field="khasra" value="{{ $line['khasra'] ?? '' }}" maxlength="255" autocomplete="off">
        </div>
    </div>

    <div class="mt-3">
        <div class="fw-semibold small mb-2">Area <span class="text-danger">*</span> <span class="text-muted fw-normal">(whole numbers)</span></div>
        <div class="row g-3">
            <div class="col-6 col-md-3">
                <label class="form-label">Acre</label>
                <input type="number" class="form-control form-control-theme" data-line-field="area_acre" value="{{ (int) ($line['area_acre'] ?? 0) }}" min="0" step="1" required>
            </div>
            <div class="col-6 col-md-3">
                <label class="form-label">Kanal</label>
                <input type="number" class="form-control form-control-theme" data-line-field="area_kanal" value="{{ (int) ($line['area_kanal'] ?? 0) }}" min="0" step="1" required>
            </div>
            <div class="col-6 col-md-3">
                <label class="form-label">Marla</label>
                <input type="number" class="form-control form-control-theme" data-line-field="area_marla" value="{{ (int) ($line['area_marla'] ?? 0) }}" min="0" step="1" required>
            </div>
            <div class="col-6 col-md-3">
                <label class="form-label">Sq ft</label>
                <input type="number" class="form-control form-control-theme" data-line-field="area_sqft" value="{{ (int) ($line['area_sqft'] ?? 0) }}" min="0" step="1" required>
            </div>
            <div class="col-md-4">
                <label class="form-label">Amount per acre (Rs) <span class="text-danger">*</span></label>
                <input type="number" class="form-control form-control-theme" data-line-field="amount_per_acre" value="{{ isset($line['amount_per_acre']) ? $line['amount_per_acre'] : '' }}" min="0" step="0.01" required placeholder="0">
            </div>
        </div>
    </div>
</div>
