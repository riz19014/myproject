@extends('layouts.app')

@section('title', 'Daily entry report')

@section('main_class', 'container-fluid px-3 py-3')

@push('head')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/flatpickr.min.css">
@endpush

@section('content')
@include('daybook.partials.page-styles')

<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
    <div>
        <h1 class="mb-1 d-flex align-items-center gap-2">
            <i class="bi bi-file-earmark-bar-graph" aria-hidden="true"></i>
            <span>Daily entry report</span>
        </h1>
        <p class="text-muted small mb-0">Filter daybook entries by entry date or created-at range.</p>
    </div>
</div>

<div class="card card-theme mb-4">
    <div class="card-body">
        <form method="get" action="{{ route('sale.exemption.report') }}" class="row g-3 align-items-end" id="daily-entry-report-form">
            <input type="hidden" name="_filter" value="1">

            <div class="col-12 col-md-auto">
                <span class="form-label small text-muted d-block mb-1">Filter by</span>
                <div class="btn-group" role="group" aria-label="Filter column">
                    <input type="radio" class="btn-check" name="filter_by" id="filter_by_date" value="date" autocomplete="off" @checked($filterBy === 'date')>
                    <label class="btn btn-outline-theme btn-sm" for="filter_by_date">Date</label>

                    <input type="radio" class="btn-check" name="filter_by" id="filter_by_created_at" value="created_at" autocomplete="off" @checked($filterBy === 'created_at')>
                    <label class="btn btn-outline-theme btn-sm" for="filter_by_created_at">Created at</label>
                </div>
            </div>

            <div class="col-6 col-md-auto">
                <label for="daily-report-from" class="form-label small text-muted mb-1">From</label>
                <input
                    type="text"
                    name="from"
                    id="daily-report-from"
                    class="form-control form-control-theme"
                    value="{{ $from ?? '' }}"
                    placeholder="From date"
                    inputmode="none"
                    autocomplete="off"
                    readonly
                    required
                >
            </div>

            <div class="col-6 col-md-auto">
                <label for="daily-report-to" class="form-label small text-muted mb-1">To</label>
                <input
                    type="text"
                    name="to"
                    id="daily-report-to"
                    class="form-control form-control-theme"
                    value="{{ $to ?? '' }}"
                    placeholder="To date"
                    inputmode="none"
                    autocomplete="off"
                    readonly
                    required
                >
            </div>

            <div class="col-12 col-md-auto d-flex flex-wrap gap-2">
                <button type="submit" class="btn btn-theme">Show report</button>
                <a href="{{ route('sale.exemption.report') }}" class="btn btn-outline-theme">Clear</a>
            </div>
        </form>
        <p class="small text-muted mb-0 mt-2" id="daily-report-filter-hint">
            @if($filterBy === 'created_at')
                Range applies to <strong>created_at</strong>.
            @else
                Range applies to <strong>entry_date</strong>.
            @endif
        </p>
    </div>
</div>

@if(! $filtered)
    <div class="card card-theme">
        <div class="card-body">
            <p class="text-muted small mb-0">Choose Date or Created at, set From → To, then Show report.</p>
        </div>
    </div>
@else
    <div class="card card-theme">
        <div class="card-body p-3 p-lg-4">
            @php
                $exportQuery = [
                    'filter_by' => $filterBy,
                    'from' => $from,
                    'to' => $to,
                    '_filter' => 1,
                ];
            @endphp
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                <p class="small text-muted mb-0">
                    {{ $filterBy === 'created_at' ? 'Created at' : 'Entry date' }}:
                    <strong>{{ \Carbon\Carbon::parse($from)->format('j M Y') }}</strong>
                    —
                    <strong>{{ \Carbon\Carbon::parse($to)->format('j M Y') }}</strong>
                    · {{ $entries->count() }} record{{ $entries->count() === 1 ? '' : 's' }}
                </p>
                <div class="d-flex flex-wrap gap-2">
                    <a
                        href="{{ route('sale.exemption.report.pdf', $exportQuery) }}"
                        class="btn btn-sm btn-outline-theme d-inline-flex align-items-center gap-1"
                        target="_blank"
                        rel="noopener"
                    >
                        <i class="bi bi-file-earmark-pdf" aria-hidden="true"></i>
                        <span>PDF</span>
                    </a>
                    <a
                        href="{{ route('sale.exemption.report.excel', $exportQuery) }}"
                        class="btn btn-sm btn-outline-theme d-inline-flex align-items-center gap-1"
                    >
                        <i class="bi bi-file-earmark-excel" aria-hidden="true"></i>
                        <span>Excel</span>
                    </a>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-theme table-striped mb-0 align-middle">
                    <thead>
                        <tr>
                            <th style="width: 56px;">#</th>
                            <th class="text-nowrap">Date</th>
                            <th class="text-nowrap">Created at</th>
                            <th class="text-nowrap">Voucher</th>
                            <th>Description</th>
                            <th>Linked to</th>
                            <th class="text-end text-nowrap">Amount (Rs)</th>
                            <th class="text-end text-nowrap">Balance (Rs)</th>
                            <th class="text-end" style="width: 90px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($entries as $e)
                            @php
                                $isCashIn = $e->type === \App\Models\DayBookEntry::TYPE_CASH_IN;
                                $createdAtPk = $e->created_at
                                    ? $e->created_at->copy()->timezone('Asia/Karachi')
                                    : null;
                                $createdAtDisplay = $createdAtPk ? $createdAtPk->format('j M Y, h:i A') : '—';
                                $typeLabel = $isCashIn ? 'Payment in' : 'Payment out';
                                $runningBalance = (float) ($e->running_balance ?? 0);
                                $categoryName = $e->partySubCategory?->category?->name ?? '';
                                $daybookModalData = [
                                    'id' => $e->id,
                                    'url' => route('daybook.show', $e),
                                    'voucher' => $e->getVoucherNumber(),
                                    'date' => $e->entry_date->format('j M Y'),
                                    'created_at' => $createdAtDisplay,
                                    'type_label' => $typeLabel,
                                    'is_cash_in' => $isCashIn,
                                    'amount' => number_format((float) $e->amount, 0),
                                    'settlement' => $e->getSettlementLabel(),
                                    'paid_by' => $e->getPaidByLabel(),
                                    'description' => $e->description ?: '—',
                                    'link_label' => $e->getLinkLabel(),
                                    'project_name' => $e->project?->name ?? '',
                                    'project_is_dha' => $e->project?->isDha(),
                                    'land_type' => $e->project?->landType?->name ?? '',
                                    'purchase_file' => $e->purchaseFile?->file_name ?? '',
                                    'sold_area' => $e->getSoldAreaLabel(),
                                    'category' => $categoryName,
                                    'sub_category' => $e->getPartySubCategoryLabel(),
                                    'is_factory' => $e->isFactoryExpense(),
                                    'factory_sub_category' => $e->getFactorySubCategoryLabel(),
                                    'unit' => $e->unit ?? '',
                                    'quantity' => $e->quantity !== null ? number_format((int) $e->quantity) : '',
                                    'unit_price' => $e->unit_price !== null ? number_format((float) $e->unit_price, 2) : '',
                                ];
                            @endphp
                            <tr>
                                <td class="text-muted small">{{ $loop->iteration }}</td>
                                <td class="text-nowrap small">
                                    <a href="{{ route('daybook.index', ['date' => $e->entry_date->toDateString()]) }}" class="text-decoration-none text-body fw-medium">
                                        {{ $e->entry_date->format('j M Y') }}
                                    </a>
                                </td>
                                <td class="text-nowrap small text-muted" title="Pakistan time (Asia/Karachi)">
                                    {{ $createdAtDisplay }}
                                </td>
                                <td class="text-nowrap small font-monospace">{{ $e->getVoucherNumber() }}</td>
                                <td class="small">{{ $e->description ?: '—' }}</td>
                                <td class="small">{{ $e->getLinkLabel() }}</td>
                                <td class="text-end font-monospace small text-nowrap fw-semibold {{ $isCashIn ? 'text-success' : 'text-danger' }}">
                                    {{ $isCashIn ? '+' : '−' }}{{ number_format($e->amount, 0) }}
                                    <span class="d-block small fw-normal text-muted">{{ $typeLabel }}</span>
                                </td>
                                <td class="text-end font-monospace small text-nowrap fw-semibold">
                                    {{ $runningBalance >= 0 ? '' : '−' }}{{ number_format(abs($runningBalance), 0) }}
                                </td>
                                <td class="text-end text-nowrap">
                                    <button
                                        type="button"
                                        class="btn btn-sm btn-pink daybook-entries-view-btn"
                                        data-daybook-entry="{{ json_encode($daybookModalData, JSON_HEX_APOS | JSON_HEX_QUOT) }}"
                                    >View</button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-muted small py-4 text-center">No entries in this range.</td>
                            </tr>
                        @endforelse
                    </tbody>
                    @if($entries->isNotEmpty())
                        <tfoot>
                            <tr>
                                <td colspan="6" class="text-end fw-bold">Total</td>
                                <td class="text-end font-monospace">
                                    <div class="text-success">+{{ number_format($cashIn, 0) }}</div>
                                    <div class="text-danger">−{{ number_format($cashOut, 0) }}</div>
                                    <div>
                                        <strong>
                                            Net {{ ($cashIn - $cashOut) >= 0 ? '+' : '−' }}{{ number_format(abs($cashIn - $cashOut), 0) }}
                                        </strong>
                                    </div>
                                </td>
                                <td class="text-end font-monospace fw-bold">
                                    {{ ($closingBalance ?? ($cashIn - $cashOut)) >= 0 ? '' : '−' }}{{ number_format(abs($closingBalance ?? ($cashIn - $cashOut)), 0) }}
                                </td>
                                <td></td>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>
        </div>
    </div>

    <div class="modal fade" id="daybookEntryModal" tabindex="-1" aria-labelledby="daybookEntryModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content daybook-entry-modal-content">
                <div class="modal-header">
                    <div>
                        <h5 class="modal-title mb-0" id="daybookEntryModalLabel">Daybook entry</h5>
                        <p class="small text-muted mb-0" id="daybook-modal-subtitle"></p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="daybook-modal-grid">
                        <div class="daybook-modal-item"><span class="daybook-modal-key">Voucher no.</span><span id="daybook-modal-voucher" class="daybook-modal-val font-monospace fw-semibold"></span></div>
                        <div class="daybook-modal-item"><span class="daybook-modal-key">Date</span><span id="daybook-modal-date" class="daybook-modal-val"></span></div>
                        <div class="daybook-modal-item"><span class="daybook-modal-key">Created at</span><span id="daybook-modal-created-at" class="daybook-modal-val"></span></div>
                        <div class="daybook-modal-item"><span class="daybook-modal-key">Payment</span><span id="daybook-modal-type" class="daybook-modal-val"></span></div>
                        <div class="daybook-modal-item"><span class="daybook-modal-key">Amount</span><span id="daybook-modal-amount" class="daybook-modal-val font-monospace fw-semibold"></span></div>
                        <div class="daybook-modal-item"><span class="daybook-modal-key">Settlement</span><span id="daybook-modal-settlement" class="daybook-modal-val"></span></div>
                        <div class="daybook-modal-item"><span class="daybook-modal-key">Linked to</span><span id="daybook-modal-link" class="daybook-modal-val"></span></div>
                        <div class="daybook-modal-item"><span class="daybook-modal-key">Project</span><span id="daybook-modal-project" class="daybook-modal-val"></span></div>
                        <div class="daybook-modal-item"><span class="daybook-modal-key">Sale file</span><span id="daybook-modal-file" class="daybook-modal-val"></span></div>
                        <div class="daybook-modal-item"><span class="daybook-modal-key">Sold area</span><span id="daybook-modal-sold-area" class="daybook-modal-val"></span></div>
                        <div class="daybook-modal-item"><span class="daybook-modal-key">Party sub category</span><span id="daybook-modal-subcat" class="daybook-modal-val"></span></div>
                        <div class="daybook-modal-item daybook-modal-item--wide"><span class="daybook-modal-key">Description</span><span id="daybook-modal-description" class="daybook-modal-val"></span></div>
                    </div>

                    <div id="daybook-modal-factory" class="mt-3 d-none">
                        <h6 class="text-uppercase text-muted small mb-2 d-flex align-items-center gap-2">
                            <i class="bi bi-box-seam" aria-hidden="true"></i>
                            <span>Factory expense</span>
                        </h6>
                        <div class="daybook-modal-grid">
                            <div class="daybook-modal-item daybook-modal-item--wide"><span class="daybook-modal-key">Sub category</span><span id="daybook-modal-fsubcat" class="daybook-modal-val"></span></div>
                            <div class="daybook-modal-item"><span class="daybook-modal-key">Unit</span><span id="daybook-modal-unit" class="daybook-modal-val"></span></div>
                            <div class="daybook-modal-item"><span class="daybook-modal-key">Quantity</span><span id="daybook-modal-qty" class="daybook-modal-val"></span></div>
                            <div class="daybook-modal-item"><span class="daybook-modal-key">Unit price</span><span id="daybook-modal-price" class="daybook-modal-val"></span></div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <a href="#" id="daybook-modal-open-full" class="btn btn-outline-theme btn-sm">Open full page</a>
                    <button type="button" class="btn btn-pink btn-sm" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
@endif
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/flatpickr.min.js"></script>
<script>
(function () {
    var fromInput = document.getElementById('daily-report-from');
    var toInput = document.getElementById('daily-report-to');
    var hint = document.getElementById('daily-report-filter-hint');
    if (!fromInput || !toInput || typeof flatpickr === 'undefined') return;

    var fromPicker = flatpickr(fromInput, {
        dateFormat: 'Y-m-d',
        allowInput: false,
        onChange: function (selectedDates) {
            if (selectedDates[0] && toPicker) {
                toPicker.set('minDate', selectedDates[0]);
            }
        }
    });
    var toPicker = flatpickr(toInput, {
        dateFormat: 'Y-m-d',
        allowInput: false,
        onChange: function (selectedDates) {
            if (selectedDates[0] && fromPicker) {
                fromPicker.set('maxDate', selectedDates[0]);
            }
        }
    });

    if (fromInput.value && toPicker) toPicker.set('minDate', fromInput.value);
    if (toInput.value && fromPicker) fromPicker.set('maxDate', toInput.value);

    document.querySelectorAll('input[name="filter_by"]').forEach(function (el) {
        el.addEventListener('change', function () {
            if (!hint) return;
            if (el.value === 'created_at') {
                hint.innerHTML = 'Range applies to <strong>created_at</strong>.';
            } else {
                hint.innerHTML = 'Range applies to <strong>entry_date</strong>.';
            }
        });
    });
})();

(function () {
    var modalEl = document.getElementById('daybookEntryModal');
    if (!modalEl || typeof bootstrap === 'undefined') return;
    if (modalEl.parentNode !== document.body) {
        document.body.appendChild(modalEl);
    }
    var modal = bootstrap.Modal.getOrCreateInstance(modalEl);

    function setRow(id, value) {
        var el = document.getElementById(id);
        if (!el) return;
        el.textContent = (value === null || value === undefined || value === '') ? '—' : value;
        var item = el.closest('.daybook-modal-item');
        if (item) item.classList.toggle('d-none', el.textContent === '—');
    }

    function openFromButton(btn) {
        var data;
        try {
            data = JSON.parse(btn.getAttribute('data-daybook-entry')) || {};
        } catch (e) {
            return;
        }

        var subtitle = document.getElementById('daybook-modal-subtitle');
        if (subtitle) subtitle.textContent = data.voucher ? ('Voucher ' + data.voucher) : ('Entry #' + (data.id || ''));

        setRow('daybook-modal-voucher', data.voucher);
        setRow('daybook-modal-date', data.date);
        setRow('daybook-modal-created-at', data.created_at);
        setRow('daybook-modal-type', data.type_label);
        setRow('daybook-modal-settlement', data.settlement === '—' ? '' : data.settlement);
        setRow('daybook-modal-description', data.description === '—' ? '' : data.description);
        setRow('daybook-modal-link', data.link_label);

        var projectText = data.project_name || '';
        if (projectText) {
            projectText = (data.project_is_dha ? '🟢 ' : '🟡 ') + projectText;
        }
        if (projectText && data.land_type) projectText += ' (' + data.land_type + ')';
        setRow('daybook-modal-project', projectText);

        setRow('daybook-modal-file', data.purchase_file);
        setRow('daybook-modal-sold-area', (!data.sold_area || data.sold_area === '—') ? '' : data.sold_area);

        var subcat = '';
        if (data.sub_category && data.sub_category !== '—') {
            subcat = (data.category ? data.category + ' · ' : '') + data.sub_category;
        }
        setRow('daybook-modal-subcat', subcat);

        var factoryWrap = document.getElementById('daybook-modal-factory');
        if (factoryWrap) {
            if (data.is_factory) {
                factoryWrap.classList.remove('d-none');
                setRow('daybook-modal-fsubcat', data.factory_sub_category === '—' ? '' : data.factory_sub_category);
                setRow('daybook-modal-unit', data.unit);
                setRow('daybook-modal-qty', data.quantity);
                setRow('daybook-modal-price', data.unit_price ? ('Rs ' + data.unit_price) : '');
            } else {
                factoryWrap.classList.add('d-none');
            }
        }

        var amountEl = document.getElementById('daybook-modal-amount');
        if (amountEl) {
            amountEl.textContent = data.amount || '—';
            amountEl.classList.remove('daybook-amount--in', 'daybook-amount--out');
            amountEl.classList.add(data.is_cash_in ? 'daybook-amount--in' : 'daybook-amount--out');
        }

        var openFull = document.getElementById('daybook-modal-open-full');
        if (openFull && data.url) openFull.setAttribute('href', data.url);

        modal.show();
    }

    document.querySelectorAll('.daybook-entries-view-btn[data-daybook-entry]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            openFromButton(btn);
        });
    });
})();
</script>
@endpush
