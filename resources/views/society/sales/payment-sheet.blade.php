@extends('layouts.app')

@section('title', 'Payment sheet — '.($booking->plot?->plot_number ?? '#'.$booking->id))

@section('content')
@include('society.partials.nav')

@php
    $plot = $booking->plot;
    $fileNo = $plot?->plot_number ?? ('#'.$booking->id);
    $buyer = $booking->party?->name ?: ($booking->customer?->name ?? '—');
    $areaLabel = $plot
        ? \App\Support\LandMeasure::formatAkmsLabelFromMarla((float) $plot->land_area_marla)
        : '—';
    $salePrice = (float) $booking->total_price;
    $received = $booking->fileReceivedAmount();
    $outstanding = $booking->fileOutstandingAmount();
    $runningBalance = $salePrice;
    $canIgnoreOutstanding = $canIgnoreOutstanding ?? false;
    $money = static function (float $amount): string {
        // Show paisa when present so leftovers like 0.33 are visible.
        $decimals = abs($amount - round($amount)) > 0.00001 ? 2 : 0;

        return number_format($amount, $decimals);
    };
@endphp

<div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-4">
    <div>
        <h1 class="h3 mb-1">Payment sheet — {{ $fileNo }}</h1>
        <p class="text-muted mb-0">{{ $buyer }} · {{ $areaLabel }} · Sale {{ $booking->booking_date?->format('d M Y') ?? '—' }}</p>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <a href="{{ route('society.sales.show', [$project, $booking]) }}" class="btn btn-sm btn-outline-theme">View file</a>
        <a href="{{ route('society.sales', $project) }}" class="btn btn-sm btn-outline-theme">Back to list</a>
        <button type="button" class="btn btn-sm btn-outline-theme" onclick="window.print()">Print</button>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif
@error('outstanding')
    <div class="alert alert-danger">{{ $message }}</div>
@enderror

<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card card-theme"><div class="card-body py-3">
            <div class="text-muted small">Sale price</div>
            <div class="fs-5 fw-semibold font-monospace">Rs {{ $money($salePrice) }}</div>
        </div></div>
    </div>
    <div class="col-md-4">
        <div class="card card-theme"><div class="card-body py-3">
            <div class="text-muted small">Received</div>
            <div class="fs-5 fw-semibold font-monospace">Rs {{ $money($received) }}</div>
        </div></div>
    </div>
    <div class="col-md-4">
        <div class="card card-theme"><div class="card-body py-3">
            <div class="text-muted small">Outstanding</div>
            <div class="fs-5 fw-semibold font-monospace {{ $outstanding > 0.009 ? 'text-danger' : '' }}">Rs {{ $money($outstanding) }}</div>
            @if($canIgnoreOutstanding)
                <form method="post" action="{{ route('society.sales.ignore-outstanding', [$project, $booking]) }}" class="mt-2 d-print-none">
                    @csrf
                    <button
                        type="button"
                        class="btn btn-sm btn-pink btn-delete-confirm"
                        data-title="Ignore leftover?"
                        data-text="Ignore leftover Rs {{ number_format($outstanding, 2) }} and mark this file as fully paid / sold? Sale price will be adjusted to match received."
                        data-confirm="Yes, ignore leftover"
                    >Ignore leftover Rs {{ number_format($outstanding, 2) }}</button>
                    <div class="form-text mb-0">Adjusts sale price to match received (max Rs {{ number_format($ignoreOutstandingMax ?? 1, 2) }}).</div>
                </form>
            @endif
        </div></div>
    </div>
</div>

<div class="card card-theme">
    <div class="card-body p-0">
        <div class="px-3 pt-3 pb-2">
            <h5 class="mb-0">Payment sheet</h5>
        </div>
        <div class="table-responsive">
            <table class="table table-theme mb-0 align-middle">
                <thead>
                    <tr>
                        <th>Sr</th>
                        <th>Date</th>
                        <th>Voucher</th>
                        <th>Method</th>
                        <th>Description</th>
                        <th class="text-end">Received (Rs)</th>
                        <th class="text-end">Balance (Rs)</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($receipts as $receipt)
                        @php
                            $runningBalance = max(0, round($runningBalance - (float) $receipt->amount, 2));
                        @endphp
                        <tr>
                            <td class="font-monospace">{{ $loop->iteration }}</td>
                            <td>{{ $receipt->entry_date?->format('d M Y') ?? '—' }}</td>
                            <td class="font-monospace">{{ $receipt->getVoucherNumber() }}</td>
                            <td>{{ $receipt->payment_method ?: '—' }}</td>
                            <td>{{ $receipt->description ?: '—' }}</td>
                            <td class="text-end font-monospace">{{ $money((float) $receipt->amount) }}</td>
                            <td class="text-end font-monospace">{{ $money($runningBalance) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-muted text-center py-4">No daybook receipt yet</td>
                        </tr>
                    @endforelse
                </tbody>
                @if($receipts->isNotEmpty())
                    <tfoot>
                        <tr>
                            <th colspan="5" class="text-end">Total received</th>
                            <th class="text-end font-monospace">{{ $money($received) }}</th>
                            <th class="text-end font-monospace">{{ $money($outstanding) }}</th>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>
    </div>
</div>
@endsection
