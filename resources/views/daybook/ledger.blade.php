@extends('layouts.app')

@section('title', 'Daybook ledger')

@section('main_class', 'container-fluid px-3 pb-4 pt-0')

@push('head')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/flatpickr.min.css">
@endpush

@section('content')
<div class="no-print mb-3">
    <div class="d-flex flex-wrap align-items-end gap-3">
        <form method="get" action="{{ route('daybook.ledger') }}" class="d-flex flex-wrap align-items-end gap-2" id="ledger-filter-form">
            <div>
                <label for="ledger-from" class="form-label small text-muted mb-0">From</label>
                <input type="text" name="from" id="ledger-from" class="form-control form-control-theme" value="{{ $from->format('Y-m-d') }}" autocomplete="off" required>
            </div>
            <div>
                <label for="ledger-to" class="form-label small text-muted mb-0">To</label>
                <input type="text" name="to" id="ledger-to" class="form-control form-control-theme" value="{{ $to->format('Y-m-d') }}" autocomplete="off" required>
            </div>
            <button type="submit" class="btn btn-theme">Show ledger</button>
        </form>
        <button type="button" class="btn btn-outline-theme" onclick="window.print()">Print</button>
        <a class="btn btn-outline-theme" href="{{ route('daybook.ledger.pdf', ['from' => $from->format('Y-m-d'), 'to' => $to->format('Y-m-d')]) }}">Download PDF</a>
        <a class="btn btn-outline-theme" href="{{ route('daybook.index') }}">Daybook</a>
    </div>
</div>

<div class="card card-theme daybook-ledger-print mb-4">
    <div class="card-body">
        <h1 class="h4 mb-1">Daybook ledger</h1>
        <p class="text-muted small mb-3">Period: {{ $from->format('l, j M Y') }} to {{ $to->format('l, j M Y') }}</p>

        <div class="rounded border bg-light-subtle p-3 mb-4" style="border-color: rgba(15, 23, 42, 0.12) !important;">
            <div class="row g-2 text-center small">
                <div class="col-6 col-md-3">
                    <div class="text-muted">Payment in (range)</div>
                    <div class="fw-semibold text-success">Rs {{ number_format($grandCashIn, 0) }}</div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="text-muted">Payment out (range)</div>
                    <div class="fw-semibold text-danger">Rs {{ number_format($grandCashOut, 0) }}</div>
                </div>
            </div>
        </div>

        @forelse($ledgerDays as $L)
            @php
                $day = $L['day'];
                $prevDay = $L['prevDay'];
                $previousDayClosing = $L['previousDayClosing'];
                $openingAmount = $L['openingAmount'];
                $pettyCashAmount = $L['pettyCashAmount'];
                $cashIn = $L['cashIn'];
                $cashOut = $L['cashOut'];
                $closingBalance = $L['closingBalance'];
                $tableRows = $L['tableRows'];
            @endphp
            <section class="mb-4 pb-4 border-bottom" style="border-color: rgba(15, 23, 42, 0.1) !important;">
                <h2 class="h6 text-primary-emphasis mb-2">{{ $day->format('l, j M Y') }}</h2>
                <p class="small text-muted mb-2">Previous day ({{ $prevDay->format('l, j M Y') }}) closing: <strong>Rs {{ number_format($previousDayClosing, 0) }}</strong></p>

                <div class="table-responsive mb-3">
                    <table class="table table-sm table-bordered mb-0">
                        <tbody>
                            <tr class="table-light">
                                <th class="text-muted small" style="width:20%">Opening</th>
                                <td>Rs {{ number_format($openingAmount, 0) }}</td>
                                <th class="text-muted small" style="width:20%">Petty cash</th>
                                <td>Rs {{ number_format($pettyCashAmount, 0) }}</td>
                            </tr>
                            <tr class="table-light">
                                <th class="text-muted small">Payment in</th>
                                <td class="text-success">Rs {{ number_format($cashIn, 0) }}</td>
                                <th class="text-muted small">Payment out</th>
                                <td class="text-danger">Rs {{ number_format($cashOut, 0) }}</td>
                            </tr>
                            <tr class="table-light">
                                <th class="text-muted small" colspan="2">Closing balance</th>
                                <td class="fw-bold text-success" colspan="2">Rs {{ number_format($closingBalance, 0) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="table-responsive">
                    <table class="table table-theme table-sm mb-0">
                        <thead>
                            <tr>
                                <th>Description</th>
                                <th>Payment</th>
                                <th class="text-end">Amount</th>
                                <th class="text-end">Balance</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr class="table-secondary">
                                <td colspan="2">Opening balance (carried)</td>
                                <td class="text-end"></td>
                                <td class="text-end fw-medium">Rs {{ number_format($openingAmount, 0) }}</td>
                            </tr>
                            <tr class="table-secondary">
                                <td colspan="2">Petty cash</td>
                                <td class="text-end">Rs {{ number_format($pettyCashAmount, 0) }}</td>
                                <td class="text-end fw-medium">Rs {{ number_format($openingAmount + $pettyCashAmount, 0) }}</td>
                            </tr>
                            @foreach($tableRows as $row)
                                <tr>
                                    <td>{{ $row['description'] }}</td>
                                    <td>{{ $row['type_label'] }}</td>
                                    <td class="text-end">{{ $row['amount_str'] }}</td>
                                    <td class="text-end">Rs {{ number_format($row['balance'], 0) }}</td>
                                </tr>
                            @endforeach
                            <tr class="fw-bold border-top border-2">
                                <td colspan="2">Closing balance</td>
                                <td class="text-end"></td>
                                <td class="text-end text-success">Rs {{ number_format($closingBalance, 0) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>
        @empty
            <p class="text-muted mb-0">No ledger rows for this range.</p>
        @endforelse
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/flatpickr.min.js"></script>
<script>
(function () {
    if (typeof flatpickr === 'undefined') return;
    var opts = { dateFormat: 'Y-m-d', allowInput: false, disableMobile: true, clickOpens: true };
    var fromEl = document.getElementById('ledger-from');
    var toEl = document.getElementById('ledger-to');
    if (fromEl) flatpickr(fromEl, opts);
    if (toEl) flatpickr(toEl, opts);
})();
</script>
@endpush
