@extends('layouts.app')

@section('title', 'DayBook Entry')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1>Entry #{{ $entry->id }}</h1>
    <div class="d-flex flex-wrap gap-2">
        <a href="{{ route('daybook.voucher', $entry) }}" class="btn btn-pink" target="_blank" rel="noopener"><i class="bi bi-printer me-1" aria-hidden="true"></i>Print voucher</a>
        <a href="{{ route('daybook.edit', $entry) }}" class="btn btn-outline-theme">Edit</a>
        <a href="{{ route('daybook.index', ['date' => $entry->entry_date->toDateString()]) }}" class="btn btn-outline-theme">Back to DayBook</a>
    </div>
</div>

<div class="card card-theme">
    <div class="card-body">
        <table class="table table-theme">
            <tr><th width="180">Voucher no.</th><td class="font-monospace fw-semibold">{{ $entry->getVoucherNumber() }}</td></tr>
            <tr><th width="180">Date</th><td>{{ $entry->entry_date->format('d M Y') }}</td></tr>
            <tr><th>Payment</th><td>{{ $entry->type === 'cash_in' ? 'Payment in' : 'Payment out' }}</td></tr>
            <tr><th>Settlement</th><td>{{ $entry->getSettlementLabel() }}</td></tr>
            <tr><th>Amount</th><td>{{ number_format($entry->amount) }}</td></tr>
            <tr><th>Description</th><td>{{ $entry->description ?? '—' }}</td></tr>
            <tr><th>Linked To</th><td>{{ $entry->getLinkLabel() }}</td></tr>
            <tr><th>Purchase file</th><td>{{ $entry->getPurchaseFileLabel() }}</td></tr>
            <tr><th>Party sub category</th><td>{{ $entry->getPartySubCategoryLabel() }}</td></tr>
        </table>
    </div>
</div>
@endsection
