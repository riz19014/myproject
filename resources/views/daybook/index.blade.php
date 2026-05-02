@extends('layouts.app')

@section('title', 'Daybook')

@section('main_class', 'container-fluid px-3 pb-4 pt-0')

@push('head')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/flatpickr.min.css">
@endpush

@section('content')
@include('daybook.partials.page-styles')
<div class="daybook-page">
    <div class="daybook-page-heading">
        <div class="daybook-card-heading">
            <div class="daybook-card-heading__title">
                <h1 class="daybook-card-title">Daybook</h1>
            </div>
            <div class="daybook-card-heading__actions">
                <div class="daybook-date-nav" role="group" aria-label="Daybook date navigation">
                    <a
                        href="{{ route('daybook.index', ['date' => $day->copy()->subDay()->toDateString()]) }}"
                        class="daybook-nav-btn"
                        aria-label="Previous day"
                        title="Previous day"
                    >
                        <i class="bi bi-chevron-left" aria-hidden="true"></i>
                    </a>

                    <form method="get" action="{{ route('daybook.index') }}" class="m-0">
                        <label class="visually-hidden" for="daybook-filter-date">Select date</label>
                        <div class="daybook-date-picker">
                            <i class="bi bi-calendar3" aria-hidden="true"></i>
                            <input
                                type="text"
                                id="daybook-filter-date"
                                name="date"
                                class="daybook-date-input"
                                value="{{ $day->toDateString() }}"
                                inputmode="none"
                                autocomplete="off"
                                readonly
                                aria-label="View date"
                            >
                        </div>
                    </form>

                    <a
                        href="{{ route('daybook.index', ['date' => $day->copy()->addDay()->toDateString()]) }}"
                        class="daybook-nav-btn"
                        aria-label="Next day"
                        title="Next day"
                    >
                        <i class="bi bi-chevron-right" aria-hidden="true"></i>
                    </a>
                </div>

                <span class="daybook-date-chip" title="{{ $day->toDateString() }}">{{ $day->format('l, j M Y') }}</span>
                <a href="{{ route('daybook.report.pdf', ['date' => $day->toDateString()]) }}" class="daybook-pdf-link" title="Download PDF" aria-label="Download PDF for this day">
                    <svg xmlns="http://www.w3.org/2000/svg" width="26" height="26" viewBox="0 0 16 16" fill="#dc2626" aria-hidden="true">
                        <path d="M14 14V4.5L9.5 0H4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2zM9.5 3A1.5 1.5 0 0 0 11 4.5h2V14a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1h5.5v2z"/>
                        <path d="M4.603 14.087a1 1 0 0 0-.757-.429H2.5a1 1 0 0 0-1 1v1a1 1 0 0 0 1 1h1a1 1 0 0 0 .997-.867l.922-5.638h.001a.5.5 0 0 0-.497-.5H3.522a.5.5 0 0 0-.486.345l-.433 4.113zm5.21-1.57a.5.5 0 0 0-.498.453l-.922 5.638a1 1 0 0 1-.997.872h-1.003a1 1 0 0 1-1-1v-1a1 1 0 0 1 1-1h1.345a1 1 0 0 1 .757.429l.486.606a.5.5 0 0 0 .498.453zM9.5 12.5a.5.5 0 0 0-.5.5v1a.5.5 0 0 0 .5.5h1a.5.5 0 0 0 .5-.5v-1a.5.5 0 0 0-.5-.5h-1zm1.5-3.5a.5.5 0 0 0-.5-.5h-1a.5.5 0 0 0-.5.5v1a.5.5 0 0 0 .5.5h1a.5.5 0 0 0 .5-.5v-1zm-2-3a.5.5 0 0 0-.5-.5h-1a.5.5 0 0 0-.5.5v1a.5.5 0 0 0 .5.5h1a.5.5 0 0 0 .5-.5v-1z"/>
                    </svg>
                </a>
            </div>
        </div>
    </div>

<section class="daybook-metrics" aria-label="Day financial summary">
    <div class="daybook-metric daybook-metric--prior">
        <span class="daybook-metric__label">Previous close</span>
        <span class="daybook-metric__val">Rs {{ number_format($previousDayClosing, 2) }}</span>
        <span class="daybook-metric__sub">{{ $prevDay->format('D, j M') }}</span>
    </div>
    <div class="daybook-metric daybook-metric--open">
        <span class="daybook-metric__label">Opening</span>
        <span class="daybook-metric__val">Rs {{ number_format($openingAmount, 2) }}</span>
        <span class="daybook-metric__sub">Carried balance</span>
    </div>
    <div class="daybook-metric daybook-metric--petty">
        <span class="daybook-metric__label">Petty cash</span>
        <span class="daybook-metric__val">Rs {{ number_format($pettyCashAmount, 2) }}</span>
        <span class="daybook-metric__sub">Added today</span>
    </div>
    <div class="daybook-metric daybook-metric--in">
        <span class="daybook-metric__label">Payment in</span>
        <span class="daybook-metric__val">Rs {{ number_format($cashIn, 2) }}</span>
        <span class="daybook-metric__sub">This day</span>
    </div>
    <div class="daybook-metric daybook-metric--out">
        <span class="daybook-metric__label">Payment out</span>
        <span class="daybook-metric__val">Rs {{ number_format($cashOut, 2) }}</span>
        <span class="daybook-metric__sub">This day</span>
    </div>
    <div class="daybook-metric daybook-metric--close">
        <span class="daybook-metric__label">Closing</span>
        <span class="daybook-metric__val">Rs {{ number_format($closingBalance, 2) }}</span>
        <span class="daybook-metric__sub">End of day</span>
    </div>
</section>

<div class="card daybook-card mb-4">
    <div class="daybook-card__accent" aria-hidden="true"></div>
    <div class="card-body p-0">
        <div class="daybook-tabs-row">
        <ul class="nav nav-pills daybook-inner-tabs ps-0 mb-0" id="daybookMainTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="daybook-tab-new-btn" data-bs-toggle="tab" data-bs-target="#daybook-tab-new" type="button" role="tab" aria-controls="daybook-tab-new" aria-selected="true">New Entry</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="daybook-tab-records-btn" data-bs-toggle="tab" data-bs-target="#daybook-tab-records" type="button" role="tab" aria-controls="daybook-tab-records" aria-selected="false">Records</button>
            </li>
        </ul>
        <div class="daybook-tabs-row-save">
            <button type="submit" form="daybook-entry-form" class="daybook-save-record text-nowrap" id="daybook-save-record-btn" aria-label="Save record">
                <span class="daybook-save-record__idle">
                    <i class="bi bi-floppy2 app-sidebar-logout__icon" aria-hidden="true"></i>
                    <span>Save record</span>
                </span>
                <span class="daybook-save-record__busy" aria-hidden="true">
                    <span class="daybook-save-spinner" role="status" aria-hidden="true"></span>
                    <span>Saving…</span>
                </span>
            </button>
        </div>
        </div>
        <div class="tab-content daybook-main-tab-content" id="daybookMainTabsContent">
            <div class="tab-pane fade show active" id="daybook-tab-new" role="tabpanel" aria-labelledby="daybook-tab-new-btn" tabindex="0">
                <div class="daybook-form-inner">
                    @include('daybook.partials.entry-form', [
                        'daybookFormAction' => route('daybook.store'),
                        'daybookFormUsePut' => false,
                        'daybookReturnDate' => $day->toDateString(),
                    ])
                </div>
            </div>
            <div class="tab-pane fade" id="daybook-tab-records" role="tabpanel" aria-labelledby="daybook-tab-records-btn" tabindex="0">
                <div class="daybook-records-panel">
        <div class="daybook-table-head">
            <div class="d-flex flex-wrap align-items-center gap-2">
                <h2 class="mb-0 d-flex align-items-center flex-wrap">Entries <span class="daybook-count-badge" title="Lines this day">{{ $entries->count() }}</span></h2>
            </div>
            <span class="text-muted small fw-medium">{{ $day->format('l, j M Y') }}</span>
        </div>
        <div class="table-responsive daybook-table-shell">
            <table class="table table-theme mb-0 align-middle">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Description</th>
                        <th>Payment</th>
                        <th class="text-end">Amount (Rs)</th>
                        <th>Linked to</th>
                        <th>Sub category</th>
                        <th width="220">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($entries as $e)
                        <tr>
                            <td class="daybook-id-cell">{{ $e->id }}</td>
                            <td>{{ $e->description ?: '—' }}</td>
                            <td>
                                @if($e->type === 'cash_in')
                                    <span class="daybook-pill daybook-pill--in">Payment in</span>
                                @else
                                    <span class="daybook-pill daybook-pill--out">Payment out</span>
                                @endif
                            </td>
                            <td class="text-end font-monospace">
                                @if($e->type === 'cash_in')
                                    +{{ number_format($e->amount, 0) }}
                                @else
                                    −{{ number_format($e->amount, 0) }}
                                @endif
                            </td>
                            <td class="small">{{ $e->getLinkLabel() }}</td>
                            <td class="small">{{ $e->getPartySubCategoryLabel() }}</td>
                            <td>
                                <div class="daybook-table-actions">
                                    <a href="{{ route('daybook.show', $e) }}" class="daybook-table-action-btn">View</a>
                                    <a href="{{ route('daybook.edit', $e) }}" class="daybook-table-action-btn">Edit</a>
                                    <form action="{{ route('daybook.destroy', $e) }}" method="POST" class="d-inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="button" class="daybook-table-action-btn btn-delete-confirm" data-title="Delete entry?" data-text="This will remove this daybook line.">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="daybook-empty">
                                <div class="daybook-empty__icon" aria-hidden="true">◇</div>
                                <p><strong>No lines yet</strong> for this date. Add a payment in <strong>New Entry</strong> — project, party, and amount — to build your ledger.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
                </div>
            </div>
        </div>
    </div>
</div>
</div>{{-- .daybook-page --}}
@endsection

@push('modals')
@include('daybook.partials.entry-modals')
@endpush

@push('scripts')
@include('daybook.partials.entry-form-scripts')
<script>
(function () {
    var input = document.getElementById('daybook-filter-date');
    if (!input || typeof flatpickr === 'undefined') return;
    var form = input.closest('form');
    flatpickr(input, {
        dateFormat: 'Y-m-d',
        defaultDate: input.value || null,
        allowInput: false,
        disableMobile: true,
        clickOpens: true,
        onChange: function () {
            if (form) form.submit();
        }
    });
})();
</script>
@endpush
