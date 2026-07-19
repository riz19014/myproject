@extends('layouts.app')

@section('title', 'Partners — '.$project->name)

@section('content')
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
    <div>
        <h1 class="mb-1">Partners</h1>
        <p class="text-muted small mb-0">
            Project: <strong>{{ $project->name }}</strong>
            @if($project->landType)
                <span class="text-muted">· {{ $project->landType->name }}</span>
            @endif
        </p>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <a href="{{ route('projects.show', $project) }}" class="btn btn-outline-theme">Back to project</a>
        <a href="{{ route('projects.index') }}" class="btn btn-outline-theme">All projects</a>
    </div>
</div>

@php
    $amountWords = function (float $amount): string {
        if ($amount <= 0) {
            return '';
        }
        $trim = fn (float $n) => rtrim(rtrim(number_format($n, 2, '.', ''), '0'), '.');
        if ($amount >= 10000000) {
            return $trim($amount / 10000000).' Cr';
        }
        if ($amount >= 100000) {
            return $trim($amount / 100000).' Lac';
        }
        if ($amount >= 1000) {
            return $trim($amount / 1000).' Th';
        }
        return $trim($amount);
    };
    $oldPartnerRows = old('partners', [[]]);
    $allocatedAmountsByFile = $project->partnerInvestments
        ->groupBy('purchase_file_id')
        ->map(fn ($items) => round((float) $items->sum('investment_amount'), 2));
    $allocatedSharesByFile = $project->partnerInvestments
        ->groupBy('purchase_file_id')
        ->map(fn ($items) => round((float) $items->sum('share_percentage'), 2));
    $partySearchOptions = $parties->map(fn ($party) => [
        'id' => $party->id,
        'label' => $party->name,
    ])->values();
    $fileSearchOptions = $project->purchaseFiles->map(function ($file) use ($allocatedAmountsByFile, $allocatedSharesByFile, $amountWords) {
        $total = (float) ($file->purchase_total_rs ?? 0);
        $words = $amountWords($total);

        return [
            'id' => $file->id,
            'label' => $file->file_name.' — Rs '.number_format($total, 2).($words !== '' ? ' ('.$words.')' : ''),
            'total' => $total,
            'allocated' => (float) ($allocatedAmountsByFile[$file->id] ?? 0),
            'shareAllocated' => (float) ($allocatedSharesByFile[$file->id] ?? 0),
        ];
    })->values();
@endphp

<div class="card card-theme mb-4">
    <div class="card-body">
        <h2 class="h5 mb-3">Add partners</h2>
        <form action="{{ route('projects.partners.store', $project) }}" method="POST" id="add-partners-form">
            @csrf
            <div class="row g-3 align-items-end mb-1">
                <div class="col-md-5">
                    <label class="form-label">Purchase file <span class="text-danger">*</span></label>
                    <div class="daybook-form-combo partner-searchable @error('purchase_file_id') is-invalid @enderror" data-search-type="file" id="shared-file-combo">
                        <input type="hidden" name="purchase_file_id" id="shared-file-id" value="{{ old('purchase_file_id') }}">
                        <input type="search" class="form-control form-control-theme partner-search-input" placeholder="Search purchase file..." autocomplete="off" required>
                        <ul class="daybook-form-combo-list d-none partner-search-list" role="listbox" hidden></ul>
                    </div>
                    @error('purchase_file_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    <div class="form-text">This file applies to all partner rows below.</div>
                </div>
                <div class="col-md-7">
                    <div id="file-summary-panel" class="partner-file-stats d-none">
                        <div class="partner-file-stat">
                            <span class="partner-file-stat-label">File total</span>
                            <span class="partner-file-stat-value" id="stat-file-total">—</span>
                            <span class="partner-file-stat-words" id="stat-file-total-words"></span>
                        </div>
                        <div class="partner-file-stat">
                            <span class="partner-file-stat-label">Allocated</span>
                            <span class="partner-file-stat-value" id="stat-allocated">—</span>
                            <span class="partner-file-stat-words" id="stat-allocated-words"></span>
                        </div>
                        <div class="partner-file-stat partner-file-stat-available">
                            <span class="partner-file-stat-label">Available</span>
                            <span class="partner-file-stat-value" id="stat-available">—</span>
                            <span class="partner-file-stat-words" id="stat-available-words"></span>
                        </div>
                        <div class="partner-file-progress">
                            <div class="progress" style="height: 6px;">
                                <div class="progress-bar" id="stat-progress" role="progressbar" style="width: 0%;"></div>
                            </div>
                            <span class="partner-file-stat-words" id="stat-progress-label"></span>
                        </div>
                    </div>
                </div>
            </div>

            <div id="partner-rows" class="d-grid gap-3 mt-3">
                @foreach($oldPartnerRows as $i => $row)
                    <div class="border rounded-3 p-3 partner-row">
                        <div class="d-flex align-items-start gap-3">
                            <div class="row g-3 flex-grow-1">
                                <div class="col-md-6">
                                    <label class="form-label">Party <span class="text-danger">*</span></label>
                                    <div class="daybook-form-combo partner-searchable @error('partners.'.$i.'.party_id') is-invalid @enderror" data-search-type="party">
                                        <input type="hidden" name="partners[{{ $i }}][party_id]" class="partner-party-id" value="{{ $row['party_id'] ?? '' }}">
                                        <input type="search" class="form-control form-control-theme partner-search-input" placeholder="Search party..." autocomplete="off" required>
                                        <ul class="daybook-form-combo-list d-none partner-search-list" role="listbox" hidden></ul>
                                    </div>
                                    @error('partners.'.$i.'.party_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label d-flex justify-content-between align-items-center">
                                        <span>Investment amount <span class="text-danger">*</span></span>
                                        <span class="partner-amount-words small text-success fw-semibold"></span>
                                    </label>
                                    <input type="number" min="0.01" step="0.01" name="partners[{{ $i }}][investment_amount]" class="form-control form-control-theme partner-investment-input @error('partners.'.$i.'.investment_amount') is-invalid @enderror" value="{{ $row['investment_amount'] ?? '' }}" required>
                                    @error('partners.'.$i.'.investment_amount')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    <div class="partner-investment-limit-error small text-danger d-none">Investment cannot exceed the purchase file total.</div>
                                    <div class="form-text">Share: <span class="partner-share-preview fw-semibold">0.00%</span></div>
                                </div>
                            </div>
                            <button type="button" class="btn btn-sm btn-danger-theme partner-row-remove mt-4" title="Delete row" aria-label="Delete row">&times;</button>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mt-3">
                <button type="button" id="partner-row-add" class="btn btn-outline-theme">
                    <span class="fw-bold">+</span> Add row
                </button>
                <button type="submit" class="btn btn-pink px-4">Save partners</button>
            </div>
        </form>
    </div>
</div>

<div class="card card-theme mb-4">
    <div class="card-body">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
            <h2 class="h5 mb-0">Partner file investments ({{ $project->partnerInvestments->count() }})</h2>
            @php $investmentsTotal = (float) $project->partnerInvestments->sum('investment_amount'); @endphp
            <span class="small text-muted">
                Total: Rs {{ number_format($investmentsTotal, 2) }}
                @if($amountWords($investmentsTotal) !== '')
                    <span class="fw-semibold">({{ $amountWords($investmentsTotal) }})</span>
                @endif
            </span>
        </div>

        @if($project->partnerInvestments->isEmpty())
            <p class="text-muted mb-0">No purchase files assigned to partners yet.</p>
        @else
            <div class="table-responsive">
                <table class="table table-striped table-theme mb-0 align-middle">
                    <thead>
                        <tr>
                            <th>Partner</th>
                            <th>Purchase file</th>
                            <th class="text-end">File total</th>
                            <th style="min-width: 220px;">Investment / Update</th>
                            <th class="text-end">Share %</th>
                            <th style="width: 90px;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($project->partnerInvestments as $investment)
                            @php
                                $fileTotal = (float) ($investment->purchaseFile?->purchase_total_rs
                                    ?? ($investment->share_percentage > 0
                                        ? (float) $investment->investment_amount * 100 / (float) $investment->share_percentage
                                        : 0));
                                $otherAllocated = max(
                                    0,
                                    (float) ($allocatedAmountsByFile[$investment->purchase_file_id] ?? 0)
                                        - (float) $investment->investment_amount
                                );
                                $availableForUpdate = max(0, $fileTotal - $otherAllocated);
                            @endphp
                            <tr>
                                <td class="fw-semibold">{{ $investment->party?->name ?? '—' }}</td>
                                <td>{{ $investment->purchaseFile?->file_name ?? '—' }}</td>
                                <td class="text-end">
                                    Rs {{ number_format($fileTotal, 2) }}
                                    @if($amountWords($fileTotal) !== '')
                                        <div class="small text-muted">{{ $amountWords($fileTotal) }}</div>
                                    @endif
                                </td>
                                <td>
                                    <form action="{{ route('projects.partner-investments.update', [$project, $investment]) }}" method="POST" class="partner-investment-update" data-file-total="{{ $fileTotal }}" data-available="{{ $availableForUpdate }}">
                                        @csrf
                                        @method('PATCH')
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-text">Rs</span>
                                            <input type="number" min="0.01" max="{{ $availableForUpdate > 0 ? $availableForUpdate : null }}" step="0.01" name="investment_amount" class="form-control form-control-theme investment-amount-input" value="{{ $investment->investment_amount }}" required>
                                            <button type="submit" class="btn btn-outline-theme">Save</button>
                                        </div>
                                        <div class="form-text">
                                            Amount: <span class="investment-amount-words fw-semibold text-success">{{ $amountWords((float) $investment->investment_amount) ?: '—' }}</span>
                                            · Available for this partner: Rs {{ number_format($availableForUpdate, 2) }}{{ $amountWords($availableForUpdate) !== '' ? ' ('.$amountWords($availableForUpdate).')' : '' }}
                                        </div>
                                        <div class="partner-update-limit-error small text-danger d-none mt-1">Investment cannot exceed the purchase file total.</div>
                                    </form>
                                </td>
                                <td class="text-end">
                                    <span class="share-percentage-display">{{ number_format((float) $investment->share_percentage, 2) }}%</span>
                                </td>
                                <td>
                                    <form action="{{ route('projects.partner-investments.destroy', [$project, $investment]) }}" method="POST">
                                        @csrf
                                        @method('DELETE')
                                        <input type="hidden" name="rebalance" value="0" class="partner-rebalance-input">
                                        <button
                                            type="button"
                                            class="btn btn-sm btn-danger-theme partner-investment-remove"
                                            data-partner="{{ $investment->party?->name ?? 'this partner' }}"
                                            data-file="{{ $investment->purchaseFile?->file_name ?? 'this purchase file' }}"
                                        >Remove</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
@endsection

@push('head')
<style>
    .partner-file-stats {
        display: flex;
        flex-wrap: wrap;
        align-items: stretch;
        gap: .75rem;
        padding: .75rem 1rem;
        border-radius: .75rem;
        border: 1px solid rgba(var(--bs-success-rgb), .25);
        background: linear-gradient(135deg, rgba(var(--bs-success-rgb), .08), rgba(var(--bs-success-rgb), .02));
    }
    .partner-file-stat {
        display: flex;
        flex-direction: column;
        justify-content: center;
        min-width: 110px;
        flex: 1 1 0;
        padding-right: 1rem;
        border-right: 1px solid rgba(0, 0, 0, .08);
    }
    .partner-file-stat:last-of-type {
        border-right: 0;
    }
    .partner-file-stat-label {
        font-size: .7rem;
        text-transform: uppercase;
        letter-spacing: .06em;
        color: var(--bs-secondary-color, #6c757d);
    }
    .partner-file-stat-value {
        font-weight: 700;
        font-size: 1.05rem;
        white-space: nowrap;
    }
    .partner-file-stat-words {
        font-size: .75rem;
        color: var(--bs-secondary-color, #6c757d);
    }
    .partner-file-stat-available .partner-file-stat-value {
        color: var(--bs-success);
    }
    .partner-file-progress {
        flex: 1 1 100%;
        display: flex;
        align-items: center;
        gap: .5rem;
    }
    .partner-file-progress .progress {
        flex: 1 1 auto;
        background: rgba(0, 0, 0, .08);
    }
    .partner-file-progress .progress-bar {
        background: var(--bs-success);
        transition: width .2s ease;
    }
    .partner-searchable.combo-open {
        z-index: 50;
    }
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    function formatShare(value) {
        return value.toLocaleString(undefined, {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    }

    function formatAmountWords(amount) {
        if (!amount || amount <= 0) return '';
        function trim(n) {
            return parseFloat(n.toFixed(2)).toString();
        }
        if (amount >= 10000000) return trim(amount / 10000000) + ' Cr';
        if (amount >= 100000) return trim(amount / 100000) + ' Lac';
        if (amount >= 1000) return trim(amount / 1000) + ' Th';
        return trim(amount);
    }

    function formatMoney(amount) {
        return amount.toLocaleString(undefined, {
            minimumFractionDigits: 0,
            maximumFractionDigits: 2
        });
    }

    var partyOptions = @json($partySearchOptions);
    var fileOptions = @json($fileSearchOptions);

    var rowsWrap = document.getElementById('partner-rows');
    var addRowBtn = document.getElementById('partner-row-add');
    var addForm = document.getElementById('add-partners-form');
    var sharedFileCombo = document.getElementById('shared-file-combo');
    var sharedFileId = document.getElementById('shared-file-id');
    var summaryPanel = document.getElementById('file-summary-panel');

    function getSearchOptions(wrapper) {
        return wrapper.dataset.searchType === 'party' ? partyOptions : fileOptions;
    }

    function closeSearchList(wrapper) {
        var list = wrapper.querySelector('.partner-search-list');
        list.classList.add('d-none');
        list.setAttribute('hidden', '');
        wrapper.classList.remove('combo-open');
    }

    function closeOtherSearchLists(current) {
        document.querySelectorAll('.partner-searchable').forEach(function (wrapper) {
            if (wrapper !== current) closeSearchList(wrapper);
        });
    }

    function renderSearchList(wrapper) {
        closeOtherSearchLists(wrapper);
        wrapper.classList.add('combo-open');
        var input = wrapper.querySelector('.partner-search-input');
        var list = wrapper.querySelector('.partner-search-list');
        var query = input.value.trim().toLowerCase();
        var options = getSearchOptions(wrapper).filter(function (option) {
            return !query || option.label.toLowerCase().includes(query);
        });

        list.innerHTML = '';
        if (!options.length) {
            var empty = document.createElement('li');
            empty.className = 'daybook-form-combo-empty';
            empty.textContent = 'No matches found.';
            list.appendChild(empty);
        } else {
            options.forEach(function (option) {
                var li = document.createElement('li');
                var button = document.createElement('button');
                button.type = 'button';
                button.textContent = option.label;
                button.addEventListener('mousedown', function (e) { e.preventDefault(); });
                button.addEventListener('click', function () {
                    var hidden = wrapper.querySelector('input[type="hidden"]');
                    hidden.value = String(option.id);
                    input.value = option.label;
                    wrapper.classList.remove('is-invalid');
                    closeSearchList(wrapper);
                    wrapper.dispatchEvent(new CustomEvent('partner-selection-change', { bubbles: true }));
                });
                li.appendChild(button);
                list.appendChild(li);
            });
        }

        list.classList.remove('d-none');
        list.removeAttribute('hidden');
    }

    function bindSearchable(wrapper) {
        var input = wrapper.querySelector('.partner-search-input');
        var hidden = wrapper.querySelector('input[type="hidden"]');
        var selected = getSearchOptions(wrapper).find(function (option) {
            return String(option.id) === String(hidden.value);
        });
        input.value = selected ? selected.label : '';

        input.addEventListener('focus', function () { renderSearchList(wrapper); });
        input.addEventListener('input', function () {
            var exact = getSearchOptions(wrapper).find(function (option) {
                return option.label.toLowerCase() === input.value.trim().toLowerCase();
            });
            hidden.value = exact ? String(exact.id) : '';
            wrapper.dispatchEvent(new CustomEvent('partner-selection-change', { bubbles: true }));
            renderSearchList(wrapper);
        });
        input.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') closeSearchList(wrapper);
        });
    }

    function bindRow(row) {
        var investmentInput = row.querySelector('.partner-investment-input');
        var removeBtn = row.querySelector('.partner-row-remove');

        row.querySelectorAll('.partner-searchable').forEach(bindSearchable);
        investmentInput.addEventListener('input', refreshAllRows);
        investmentInput.addEventListener('blur', refreshAllRows);
        removeBtn.addEventListener('click', function () {
            if (rowsWrap.querySelectorAll('.partner-row').length <= 1) {
                row.querySelectorAll('input').forEach(function (input) { input.value = ''; });
                refreshAllRows();
                return;
            }
            row.remove();
            reindexRows();
            refreshAllRows();
        });
    }

    function selectedFileOption() {
        return fileOptions.find(function (item) {
            return String(item.id) === String(sharedFileId.value);
        }) || null;
    }

    function updateSummaryPanel(fileTotal, allocatedSoFar) {
        var option = selectedFileOption();
        if (!option || fileTotal <= 0) {
            summaryPanel.classList.add('d-none');
            return;
        }
        var available = Math.max(0, fileTotal - allocatedSoFar);
        var percentUsed = fileTotal > 0 ? Math.min(100, allocatedSoFar / fileTotal * 100) : 0;

        document.getElementById('stat-file-total').textContent = 'Rs ' + formatMoney(fileTotal);
        document.getElementById('stat-file-total-words').textContent = formatAmountWords(fileTotal);
        document.getElementById('stat-allocated').textContent = 'Rs ' + formatMoney(allocatedSoFar);
        document.getElementById('stat-allocated-words').textContent = formatAmountWords(allocatedSoFar) || '0';
        document.getElementById('stat-available').textContent = 'Rs ' + formatMoney(available);
        document.getElementById('stat-available-words').textContent = formatAmountWords(available) || '0';
        document.getElementById('stat-progress').style.width = percentUsed + '%';
        document.getElementById('stat-progress-label').textContent = formatShare(percentUsed) + '% allocated';
        summaryPanel.classList.remove('d-none');
    }

    function refreshAllRows() {
        var option = selectedFileOption();
        var fileTotal = option ? Number(option.total || 0) : 0;
        var usedAmount = option ? Number(option.allocated || 0) : 0;
        var usedShare = option ? Number(option.shareAllocated || 0) : 0;

        rowsWrap.querySelectorAll('.partner-row').forEach(function (row) {
            var investmentInput = row.querySelector('.partner-investment-input');
            var sharePreview = row.querySelector('.partner-share-preview');
            var amountWords = row.querySelector('.partner-amount-words');
            var limitError = row.querySelector('.partner-investment-limit-error');

            if (!option || fileTotal <= 0) {
                investmentInput.removeAttribute('max');
                investmentInput.classList.remove('is-invalid');
                limitError?.classList.add('d-none');
                sharePreview.textContent = '0.00%';
                if (amountWords) amountWords.textContent = formatAmountWords(Number(investmentInput.value || 0));
                return;
            }

            var remainingAmount = Math.max(0, fileTotal - usedAmount);
            var remainingShare = Math.max(0, 100 - usedShare);
            var allowedByShare = fileTotal * remainingShare / 100;
            var allowedAmount = Math.max(0, Math.min(remainingAmount, allowedByShare));
            var investment = Number(investmentInput.value || 0);
            var wasLimited = investment > allowedAmount + 0.001;

            investmentInput.max = String(Math.round(allowedAmount * 100) / 100);
            if (wasLimited) {
                investment = Math.round(allowedAmount * 100) / 100;
                investmentInput.value = String(investment);
                investmentInput.classList.add('is-invalid');
                if (limitError) {
                    limitError.textContent = 'Only Rs ' + formatMoney(allowedAmount) + ' (' + (formatAmountWords(allowedAmount) || '0') + ') remains available for this file.';
                    limitError.classList.remove('d-none');
                }
            } else {
                investmentInput.classList.remove('is-invalid');
                limitError?.classList.add('d-none');
            }

            var share = fileTotal > 0 ? (investment / fileTotal) * 100 : 0;
            usedAmount += investment;
            usedShare += share;

            sharePreview.textContent = formatShare(share) + '%';
            if (amountWords) amountWords.textContent = formatAmountWords(investment);
        });

        updateSummaryPanel(fileTotal, usedAmount);
    }

    function reindexRows() {
        rowsWrap.querySelectorAll('.partner-row').forEach(function (row, index) {
            row.querySelectorAll('input').forEach(function (field) {
                if (field.name) {
                    field.name = field.name.replace(/partners\[\d+\]/, 'partners[' + index + ']');
                }
            });
        });
    }

    addRowBtn.addEventListener('click', function () {
        var firstRow = rowsWrap.querySelector('.partner-row');
        var clone = firstRow.cloneNode(true);
        clone.querySelectorAll('input').forEach(function (input) {
            input.value = '';
            input.classList.remove('is-invalid');
            input.removeAttribute('max');
        });
        clone.querySelectorAll('.partner-searchable').forEach(function (wrapper) {
            wrapper.classList.remove('is-invalid');
        });
        clone.querySelectorAll('.partner-search-list').forEach(function (list) {
            list.innerHTML = '';
            list.classList.add('d-none');
            list.setAttribute('hidden', '');
        });
        clone.querySelectorAll('.invalid-feedback').forEach(function (el) { el.remove(); });
        var clonedLimitError = clone.querySelector('.partner-investment-limit-error');
        if (clonedLimitError) {
            clonedLimitError.classList.add('d-none');
            clonedLimitError.textContent = 'Investment cannot exceed the purchase file total.';
        }
        rowsWrap.appendChild(clone);
        reindexRows();
        bindRow(clone);
        activeSummaryRow = clone;
        refreshAllRows();
    });

    bindSearchable(sharedFileCombo);
    sharedFileCombo.addEventListener('partner-selection-change', refreshAllRows);
    rowsWrap.querySelectorAll('.partner-row').forEach(bindRow);
    refreshAllRows();

    addForm?.addEventListener('submit', function (e) {
        refreshAllRows();
        var missingSelection = false;
        addForm.querySelectorAll('.partner-searchable').forEach(function (wrapper) {
            var hidden = wrapper.querySelector('input[type="hidden"]');
            if (!hidden.value) {
                missingSelection = true;
                wrapper.classList.add('is-invalid');
            }
        });
        if (missingSelection || rowsWrap.querySelector('.partner-investment-input.is-invalid')) {
            e.preventDefault();
            alert(missingSelection
                ? 'Select a valid party and purchase file from the search results.'
                : 'The total investment and share for a purchase file cannot exceed its remaining available balance.');
        }
    });

    document.addEventListener('click', function (e) {
        document.querySelectorAll('.partner-searchable').forEach(function (wrapper) {
            if (!wrapper.contains(e.target)) closeSearchList(wrapper);
        });
    });

    document.querySelectorAll('.partner-investment-remove').forEach(function (button) {
        button.addEventListener('click', function () {
            var form = button.closest('form');
            var rebalanceInput = form.querySelector('.partner-rebalance-input');

            Swal.fire({
                title: 'Remove partner investment?',
                text: button.dataset.partner + ' will be removed from ' +
                    button.dataset.file + '. ' +
                    'Would you like to proportionally adjust the remaining partners so their investments and shares total 100%?',
                icon: 'warning',
                showCancelButton: true,
                showDenyButton: true,
                confirmButtonText: 'Remove & rebalance',
                denyButtonText: 'Remove only',
                cancelButtonText: 'Cancel',
                confirmButtonColor: '#f97316',
                denyButtonColor: '#64748b',
                background: '#fff',
                color: '#0f172a',
                customClass: {
                    popup: 'swal-light',
                    title: 'swal-title',
                    htmlContainer: 'swal-text',
                    confirmButton: 'swal-confirm',
                    denyButton: 'swal-cancel',
                    cancelButton: 'swal-cancel'
                }
            }).then(function (result) {
                if (result.isConfirmed) {
                    rebalanceInput.value = '1';
                    form.submit();
                } else if (result.isDenied) {
                    rebalanceInput.value = '0';
                    form.submit();
                }
            });
        });
    });

    document.querySelectorAll('.partner-investment-update').forEach(function (form) {
        var input = form.querySelector('.investment-amount-input');
        var display = form.closest('tr')?.querySelector('.share-percentage-display');
        var amountWords = form.querySelector('.investment-amount-words');
        var limitError = form.querySelector('.partner-update-limit-error');
        var fileTotal = Number(form.dataset.fileTotal || 0);
        var available = Number(form.dataset.available || 0);

        function updateRowShare() {
            if (!input || !display) return;
            var investment = Number(input.value || 0);
            if (fileTotal > 0 && investment > available) {
                input.value = String(available);
                investment = available;
                if (limitError) {
                    limitError.textContent = 'Only Rs ' + formatMoney(available) + ' (' + (formatAmountWords(available) || '0') + ') remains available after other partner allocations.';
                    limitError.classList.remove('d-none');
                }
                input.classList.add('is-invalid');
            } else {
                if (limitError) limitError.classList.add('d-none');
                input.classList.remove('is-invalid');
            }
            var share = fileTotal > 0 ? (investment / fileTotal) * 100 : 0;
            display.textContent = formatShare(share) + '%';
            if (amountWords) amountWords.textContent = formatAmountWords(investment) || '—';
        }

        input?.addEventListener('input', updateRowShare);
        form.addEventListener('submit', function (e) {
            var investment = Number(input?.value || 0);
            if (fileTotal > 0 && investment > available) {
                e.preventDefault();
                updateRowShare();
                alert('Investment cannot exceed the remaining available amount.');
            }
        });
    });
});
</script>
@endpush
