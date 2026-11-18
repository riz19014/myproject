@extends('layouts.app')

@section('title', 'Master plan — '.$project->name)

@push('head')
<style>
    .mp-akms-input {
        appearance: textfield;
        -moz-appearance: textfield;
        text-align: center;
        width: 4.5rem;
        min-width: 4.5rem;
    }
    .mp-akms-input::-webkit-outer-spin-button,
    .mp-akms-input::-webkit-inner-spin-button {
        -webkit-appearance: none;
        margin: 0;
    }
    .mp-balance-strip {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 0.75rem;
        margin-bottom: 1.25rem;
    }
    @media (max-width: 767.98px) {
        .mp-balance-strip { grid-template-columns: 1fr; }
    }
    .mp-balance-card {
        border: 1px solid var(--border-dark);
        border-radius: 12px;
        background: var(--card-bg);
        padding: 0.9rem 1rem;
        min-height: 4.5rem;
    }
    .mp-balance-card__label {
        font-size: 0.75rem;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        color: var(--text-muted);
        margin-bottom: 0.35rem;
        line-height: 1.2;
    }
    .mp-balance-card__value {
        font-weight: 650;
        font-variant-numeric: tabular-nums;
        font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
        font-size: 0.92rem;
        line-height: 1.35;
        min-height: 1.35em;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .mp-balance-card.is-ok .mp-balance-card__value { color: #15803d; }
    .mp-balance-card.is-over .mp-balance-card__value { color: #b91c1c; }
    .mp-row-total {
        font-size: 0.78rem;
        color: var(--text-muted);
        font-variant-numeric: tabular-nums;
        font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
        line-height: 1.3;
        min-height: 1.3em;
        white-space: nowrap;
    }
    .mp-cat-cell { min-width: 11rem; }
    .mp-table th { white-space: nowrap; }
    .mp-table td { vertical-align: middle; }
    .mp-table td.mp-akms-cell {
        width: 4.75rem;
        max-width: 4.75rem;
    }
    .mp-foot-total td {
        background: rgba(15, 23, 42, 0.03);
        font-weight: 600;
    }
    .mp-foot-akms {
        font-variant-numeric: tabular-nums;
        font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
        white-space: nowrap;
    }
</style>
@endpush

@section('content')
@include('society.partials.nav')

@php
    $blankIfZero = function ($value) {
        if ($value === null || $value === '') {
            return '';
        }
        return ((int) $value === 0) ? '' : (string) (int) $value;
    };
    $remainingDisplay = \App\Support\LandMeasure::formatAkmsLabelFromMarla((float) $variance);
@endphp

<div class="mb-3">
    <h1 class="h3 mb-1">Land distribution &amp; planning</h1>
    <p class="text-muted mb-0">Empty fields mean zero. Remaining updates as you type.</p>
</div>

<div class="mp-balance-strip" id="mp-balance-strip" data-purchased-marla="{{ $purchasedMarla }}">
    <div class="mp-balance-card">
        <div class="mp-balance-card__label">Project total</div>
        <div class="mp-balance-card__value" id="mp-purchased-label">{{ $purchasedLabel }}</div>
    </div>
    <div class="mp-balance-card">
        <div class="mp-balance-card__label">Allocated</div>
        <div class="mp-balance-card__value" id="mp-allocated-label">{{ $allocatedLabel }}</div>
    </div>
    <div class="mp-balance-card {{ $reconciled ? 'is-ok' : ($variance < 0 ? 'is-over' : '') }}" id="mp-remaining-card">
        <div class="mp-balance-card__label">Remaining balance</div>
        <div class="mp-balance-card__value" id="mp-remaining-label">{{ $remainingDisplay }}</div>
    </div>
</div>

<form method="post" action="{{ route('society.master-plan.update', $project) }}" class="card card-theme" id="mp-form">
    @csrf
    @method('PUT')
    <div class="card-body">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
            <div class="text-muted small mb-0">Edit names and areas, add new categories, or remove unused ones. Click Save to apply.</div>
            <button type="button" class="btn btn-sm btn-outline-theme" id="mp-add-category">
                <i class="bi bi-plus-lg" aria-hidden="true"></i> Add category
            </button>
        </div>
        <div class="table-responsive">
            <table class="table table-theme mp-table align-middle mb-0">
                <thead>
                    <tr>
                        <th>Category</th>
                        <th class="text-center">Acre</th>
                        <th class="text-center">Kanal</th>
                        <th class="text-center">Marla</th>
                        <th class="text-center">Sqft</th>
                        <th>Breakdown</th>
                        <th class="text-end" style="width: 5rem;">Action</th>
                    </tr>
                </thead>
                <tbody id="mp-rows">
                    @foreach($rows as $i => $row)
                        @php
                            $acre = old('rows.'.$i.'.area_acre', $row['area_acre']);
                            $kanal = old('rows.'.$i.'.area_kanal', $row['area_kanal']);
                            $marla = old('rows.'.$i.'.area_marla', $row['area_marla']);
                            $sqft = old('rows.'.$i.'.area_sqft', $row['area_sqft']);
                            $rowId = old('rows.'.$i.'.id', $row['id'] ?? '');
                            $rowCategory = old('rows.'.$i.'.category', $row['category']);
                        @endphp
                        <tr class="mp-row" data-mp-row>
                            <td class="mp-cat-cell">
                                <input type="hidden" name="rows[{{ $i }}][id]" value="{{ $rowId }}">
                                <input type="hidden" name="rows[{{ $i }}][category]" value="{{ $rowCategory }}">
                                <input
                                    type="text"
                                    class="form-control form-control-sm fw-semibold"
                                    name="rows[{{ $i }}][name]"
                                    value="{{ old('rows.'.$i.'.name', $row['label']) }}"
                                    maxlength="120"
                                    required
                                    autocomplete="off"
                                    aria-label="Category name"
                                >
                            </td>
                            <td class="mp-akms-cell">
                                <input type="number" min="0" step="1" inputmode="numeric" placeholder="—"
                                       class="form-control form-control-sm mp-akms-input" data-akms="acre"
                                       name="rows[{{ $i }}][area_acre]" value="{{ $blankIfZero($acre) }}">
                            </td>
                            <td class="mp-akms-cell">
                                <input type="number" min="0" step="1" inputmode="numeric" placeholder="—"
                                       class="form-control form-control-sm mp-akms-input" data-akms="kanal"
                                       name="rows[{{ $i }}][area_kanal]" value="{{ $blankIfZero($kanal) }}">
                            </td>
                            <td class="mp-akms-cell">
                                <input type="number" min="0" step="1" inputmode="numeric" placeholder="—"
                                       class="form-control form-control-sm mp-akms-input" data-akms="marla"
                                       name="rows[{{ $i }}][area_marla]" value="{{ $blankIfZero($marla) }}">
                            </td>
                            <td class="mp-akms-cell">
                                <input type="number" min="0" step="1" inputmode="numeric" placeholder="—"
                                       class="form-control form-control-sm mp-akms-input" data-akms="sqft"
                                       name="rows[{{ $i }}][area_sqft]" value="{{ $blankIfZero($sqft) }}">
                            </td>
                            <td class="mp-breakdown-cell">
                                <x-land-akms-tags :total-marla="(float) ($row['land_area_marla'] ?? 0)" />
                            </td>
                            <td class="text-end">
                                <button type="button" class="btn btn-sm btn-outline-danger mp-remove-row" title="Remove category">Delete</button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="mp-foot-total">
                        <td>Total of categories</td>
                        <td colspan="4" class="mp-foot-akms" id="mp-foot-allocated">{{ $allocatedLabel }}</td>
                        <td class="mp-foot-akms text-muted" id="mp-foot-remaining" colspan="2">{{ $remainingDisplay }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
        <div class="mt-3 d-flex flex-wrap gap-2">
            <button type="submit" class="btn btn-pink">Save allocations</button>
        </div>
    </div>
</form>

<template id="mp-row-template">
    <tr class="mp-row" data-mp-row>
        <td class="mp-cat-cell">
            <input type="hidden" name="rows[__INDEX__][id]" value="">
            <input type="hidden" name="rows[__INDEX__][category]" value="">
            <input
                type="text"
                class="form-control form-control-sm fw-semibold"
                name="rows[__INDEX__][name]"
                value=""
                maxlength="120"
                required
                autocomplete="off"
                aria-label="Category name"
                placeholder="Category name"
            >
        </td>
        <td class="mp-akms-cell">
            <input type="number" min="0" step="1" inputmode="numeric" placeholder="—"
                   class="form-control form-control-sm mp-akms-input" data-akms="acre"
                   name="rows[__INDEX__][area_acre]" value="">
        </td>
        <td class="mp-akms-cell">
            <input type="number" min="0" step="1" inputmode="numeric" placeholder="—"
                   class="form-control form-control-sm mp-akms-input" data-akms="kanal"
                   name="rows[__INDEX__][area_kanal]" value="">
        </td>
        <td class="mp-akms-cell">
            <input type="number" min="0" step="1" inputmode="numeric" placeholder="—"
                   class="form-control form-control-sm mp-akms-input" data-akms="marla"
                   name="rows[__INDEX__][area_marla]" value="">
        </td>
        <td class="mp-akms-cell">
            <input type="number" min="0" step="1" inputmode="numeric" placeholder="—"
                   class="form-control form-control-sm mp-akms-input" data-akms="sqft"
                   name="rows[__INDEX__][area_sqft]" value="">
        </td>
        <td class="mp-breakdown-cell">
            <x-land-akms-tags :total-marla="0" />
        </td>
        <td class="text-end">
            <button type="button" class="btn btn-sm btn-outline-danger mp-remove-row" title="Remove category">Delete</button>
        </td>
    </tr>
</template>
@endsection

@push('scripts')
<script>
(function () {
    var SQFT_PER_MARLA = 225;
    var strip = document.getElementById('mp-balance-strip');
    var purchased = strip ? parseFloat(strip.getAttribute('data-purchased-marla') || '0') : 0;
    var allocatedLabel = document.getElementById('mp-allocated-label');
    var remainingLabel = document.getElementById('mp-remaining-label');
    var remainingCard = document.getElementById('mp-remaining-card');
    var footAllocated = document.getElementById('mp-foot-allocated');
    var footRemaining = document.getElementById('mp-foot-remaining');
    var tbody = document.getElementById('mp-rows');
    var template = document.getElementById('mp-row-template');
    var addBtn = document.getElementById('mp-add-category');
    var nextIndex = {{ max(count($rows), 0) }};

    function num(el) {
        if (!el) return 0;
        var v = String(el.value || '').trim();
        if (v === '') return 0;
        var n = parseInt(v, 10);
        return isNaN(n) || n < 0 ? 0 : n;
    }

    function marlaFromAkms(acre, kanal, marla, sqft) {
        return acre * 160 + kanal * 20 + marla + sqft / SQFT_PER_MARLA;
    }

    function formatAkms(totalMarla) {
        var p = akmsParts(totalMarla);
        var sign = totalMarla < -1e-6 ? '−' : '';
        return sign + 'A ' + Math.abs(p.acre) + ' — K ' + Math.abs(p.kanal) + ' — M ' + Math.abs(p.marla) + ' — SQFT ' + Math.abs(p.sqft);
    }

    function akmsParts(totalMarla) {
        var eps = 1e-6;
        if (Math.abs(totalMarla) <= eps) {
            return { acre: 0, kanal: 0, marla: 0, sqft: 0 };
        }
        var sign = totalMarla < 0 ? -1 : 1;
        var abs = Math.abs(totalMarla);
        var whole = Math.floor(abs + eps);
        var a = Math.floor(whole / 160);
        var r = whole - a * 160;
        var k = Math.floor(r / 20);
        var m = r - k * 20;
        var frac = abs - whole;
        if (frac < 0) frac = 0;
        var sqft = frac > eps ? Math.round(frac * SQFT_PER_MARLA) : 0;
        return { acre: sign * a, kanal: sign * k, marla: sign * m, sqft: sign * sqft };
    }

    function formatUnitTotal(amount) {
        if (!isFinite(amount) || Math.abs(amount) < 1e-9) return '0';
        if (Math.abs(amount - Math.round(amount)) < 1e-9) return String(Math.round(amount));
        var s = amount.toFixed(2);
        return s.replace(/\.?0+$/, '');
    }

    function unitTotals(totalMarla) {
        return {
            acre: totalMarla / 160,
            kanal: totalMarla / 20,
            marla: totalMarla,
            sqft: totalMarla * SQFT_PER_MARLA
        };
    }

    function syncRowTags(row, totalMarla) {
        var wrap = row.querySelector('[data-akms-tags]');
        if (!wrap) return;
        var p = unitTotals(totalMarla);
        ['acre', 'kanal', 'marla', 'sqft'].forEach(function (key) {
            var el = wrap.querySelector('[data-akms-tag="' + key + '"]');
            var chip = wrap.querySelector('[data-akms-tag-wrap="' + key + '"]');
            if (el) el.textContent = formatUnitTotal(p[key]);
            if (chip) chip.classList.toggle('is-zero', Math.abs(p[key]) < 1e-9);
        });
    }

    function recalc() {
        var total = 0;
        document.querySelectorAll('[data-mp-row]').forEach(function (row) {
            var acre = num(row.querySelector('[data-akms="acre"]'));
            var kanal = num(row.querySelector('[data-akms="kanal"]'));
            var marla = num(row.querySelector('[data-akms="marla"]'));
            var sqft = num(row.querySelector('[data-akms="sqft"]'));
            var rowMarla = marlaFromAkms(acre, kanal, marla, sqft);
            total += rowMarla;
            syncRowTags(row, rowMarla);
        });

        var remaining = purchased - total;
        var allocatedText = formatAkms(total);
        var remainingText = formatAkms(remaining);

        if (allocatedLabel) allocatedLabel.textContent = allocatedText;
        if (remainingLabel) remainingLabel.textContent = remainingText;
        if (footAllocated) footAllocated.textContent = allocatedText;
        if (footRemaining) footRemaining.textContent = remainingText;
        if (remainingCard) {
            remainingCard.classList.toggle('is-ok', Math.abs(remaining) < 0.01);
            remainingCard.classList.toggle('is-over', remaining < -0.01);
        }
    }

    function bindAkms(input) {
        input.addEventListener('input', recalc);
        input.addEventListener('change', recalc);
        input.addEventListener('blur', function () {
            if (String(input.value).trim() === '0') input.value = '';
            recalc();
        });
    }

    function bindRow(row) {
        row.querySelectorAll('.mp-akms-input').forEach(bindAkms);
        var removeBtn = row.querySelector('.mp-remove-row');
        if (!removeBtn) return;
        removeBtn.addEventListener('click', function () {
            var nameInput = row.querySelector('input[name*="[name]"]');
            var label = nameInput && nameInput.value ? nameInput.value : 'this category';
            var doRemove = function () {
                row.remove();
                recalc();
            };
            if (window.Swal) {
                Swal.fire({
                    title: 'Remove category?',
                    text: 'Remove "' + label + '" from the master plan? Save allocations to confirm.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#f97316',
                    cancelButtonColor: '#64748b',
                    confirmButtonText: 'Yes, remove',
                    cancelButtonText: 'Cancel',
                    background: '#fff',
                    color: '#0f172a',
                    customClass: {
                        popup: 'swal-light',
                        title: 'swal-title',
                        htmlContainer: 'swal-text',
                        confirmButton: 'swal-confirm',
                        cancelButton: 'swal-cancel'
                    }
                }).then(function (result) {
                    if (result.isConfirmed) doRemove();
                });
            } else if (window.confirm('Remove "' + label + '"?')) {
                doRemove();
            }
        });
    }

    document.querySelectorAll('[data-mp-row]').forEach(bindRow);

    if (addBtn && template && tbody) {
        addBtn.addEventListener('click', function () {
            var html = template.innerHTML.replace(/__INDEX__/g, String(nextIndex++));
            var wrap = document.createElement('tbody');
            wrap.innerHTML = html.trim();
            var row = wrap.firstElementChild;
            if (!row) return;
            tbody.appendChild(row);
            bindRow(row);
            var nameInput = row.querySelector('input[name*="[name]"]');
            if (nameInput) nameInput.focus();
            recalc();
        });
    }
})();
</script>
@endpush
