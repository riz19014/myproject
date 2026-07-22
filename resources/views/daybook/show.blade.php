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

@php($isCashIn = $entry->type === 'cash_in')
@php($isFactory = $entry->isFactoryExpense())

<div class="row g-4">
    <div class="col-12 col-xl-8">
        <div class="card card-theme h-100">
            <div class="card-body">
                <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-3">
                    <div>
                        <span class="text-muted small d-block">Voucher no.</span>
                        <span class="font-monospace fw-semibold fs-5">{{ $entry->getVoucherNumber() }}</span>
                    </div>
                    <div class="text-end">
                        <span class="badge rounded-pill {{ $isCashIn ? 'text-bg-success' : 'text-bg-danger' }} px-3 py-2">
                            {{ $isCashIn ? 'Payment in' : 'Payment out' }}
                        </span>
                        <div class="fs-4 fw-bold mt-2 {{ $isCashIn ? 'text-success' : 'text-danger' }}">
                            Rs {{ number_format($entry->amount, 2) }}
                        </div>
                    </div>
                </div>
                <table class="table table-theme mb-0 align-middle">
                    <tr><th width="180">Date</th><td>{{ $entry->entry_date->format('d M Y') }}</td></tr>
                    <tr><th>Settlement</th><td>{{ $entry->getSettlementLabel() }}</td></tr>
                    {{-- Paid by temporarily hidden from daybook UI
                    <tr><th>Paid by</th><td>{{ $entry->getPaidByLabel() }}</td></tr>
                    --}}
                    <tr><th>Description</th><td>{{ $entry->description ?: '—' }}</td></tr>
                    <tr><th>Linked To</th><td>{{ $entry->getLinkLabel() }}</td></tr>
                    <tr>
                        <th>Project</th>
                        <td>
                            @if($entry->project)
                                <x-project-name :project="$entry->project" />
                                @if($entry->project->landType)
                                    <span class="badge text-bg-light border ms-1">{{ $entry->project->landType->name }}</span>
                                @endif
                            @else
                                —
                            @endif
                        </td>
                    </tr>
                    <tr><th>Sale file</th><td>{{ $entry->getPurchaseFileLabel() }}</td></tr>
                    <tr><th>Sold area</th><td>{{ $entry->getSoldAreaLabel() }}</td></tr>
                    <tr><th>Party sub category</th><td>{{ $entry->getPartySubCategoryLabel() }}</td></tr>
                </table>
            </div>
        </div>
    </div>

    @if($isFactory)
        <div class="col-12 col-xl-4">
            <div class="card card-theme h-100">
                <div class="card-body">
                    <h2 class="h6 text-uppercase text-muted mb-3 d-flex align-items-center gap-2">
                        <i class="bi bi-box-seam" aria-hidden="true"></i>
                        <span>Factory expense</span>
                    </h2>
                    <table class="table table-theme mb-0 align-middle">
                        <tr><th width="130">Sub category</th><td>{{ $entry->getFactorySubCategoryLabel() }}</td></tr>
                        <tr><th>Unit</th><td>{{ $entry->unit ?: '—' }}</td></tr>
                        <tr><th>Quantity</th><td>{{ $entry->quantity !== null ? number_format($entry->quantity) : '—' }}</td></tr>
                        <tr><th>Unit price</th><td>{{ $entry->unit_price !== null ? 'Rs '.number_format($entry->unit_price, 2) : '—' }}</td></tr>
                        <tr class="border-top">
                            <th>Amount</th>
                            <td class="fw-semibold">
                                Rs {{ number_format($entry->amount, 2) }}
                                @if($entry->quantity !== null && $entry->unit_price !== null)
                                    <span class="d-block text-muted small fw-normal">
                                        {{ number_format($entry->quantity) }} × Rs {{ number_format($entry->unit_price, 2) }}
                                    </span>
                                @endif
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
    @endif
</div>
@endsection
