@extends('layouts.app')

@section('title', 'Voucher '.$entry->getVoucherNumber())

@push('head')
<style>
    @media print {
        .no-print { display: none !important; }
        body { background: #fff !important; }
        .app-sidebar, .app-header, footer { display: none !important; }
        main { padding: 0 !important; margin: 0 !important; max-width: none !important; }
        .daybook-voucher-sheet {
            box-shadow: none !important;
            border: 1px solid #000 !important;
            margin: 0 !important;
        }
    }
    .daybook-voucher-sheet {
        max-width: 720px;
        margin: 0 auto;
        border: 2px solid #0f172a;
        padding: 1.5rem 1.75rem;
        background: #fff;
        color: #0f172a;
    }
    .daybook-voucher-sheet .voucher-title {
        font-size: 1.35rem;
        font-weight: 700;
        letter-spacing: 0.06em;
        text-transform: uppercase;
        margin: 0;
    }
    .daybook-voucher-sheet .voucher-no {
        font-size: 1.15rem;
        font-weight: 700;
        font-family: ui-monospace, monospace;
    }
    .daybook-voucher-sheet table.voucher-meta {
        width: 100%;
        border-collapse: collapse;
        margin-top: 1.25rem;
    }
    .daybook-voucher-sheet table.voucher-meta th {
        width: 38%;
        text-align: left;
        font-weight: 600;
        padding: 0.45rem 0.75rem 0.45rem 0;
        vertical-align: top;
        border-bottom: 1px solid #e2e8f0;
    }
    .daybook-voucher-sheet table.voucher-meta td {
        padding: 0.45rem 0;
        vertical-align: top;
        border-bottom: 1px solid #e2e8f0;
    }
    .daybook-voucher-sheet .amount-box {
        margin-top: 1.25rem;
        padding: 0.85rem 1rem;
        border: 2px solid #0f172a;
        text-align: center;
    }
    .daybook-voucher-sheet .amount-box .label {
        font-size: 0.75rem;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: #64748b;
    }
    .daybook-voucher-sheet .amount-box .value {
        font-size: 1.5rem;
        font-weight: 700;
        margin-top: 0.15rem;
    }
    .daybook-voucher-sheet .signatures {
        display: flex;
        justify-content: space-between;
        gap: 2rem;
        margin-top: 2.5rem;
        padding-top: 0.5rem;
    }
    .daybook-voucher-sheet .signatures .line {
        flex: 1;
        border-top: 1px solid #0f172a;
        padding-top: 0.35rem;
        font-size: 0.8rem;
        color: #64748b;
    }
</style>
@endpush

@section('content')
<div class="no-print d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
    <h1 class="h4 mb-0">Payment voucher</h1>
    <div class="d-flex flex-wrap gap-2">
        <button type="button" class="btn btn-pink" onclick="window.print()"><i class="bi bi-printer me-1" aria-hidden="true"></i>Print voucher</button>
        <a href="{{ route('daybook.show', $entry) }}" class="btn btn-outline-theme">Back to entry</a>
        <a href="{{ route('daybook.index', ['date' => $entry->entry_date->toDateString()]) }}" class="btn btn-outline-theme">Daybook</a>
    </div>
</div>

<article class="daybook-voucher-sheet card card-theme">
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 border-bottom border-secondary border-opacity-25 pb-3 mb-0">
        <div>
            <p class="voucher-title mb-1">Payment voucher</p>
            <p class="text-muted small mb-0">Daybook entry #{{ $entry->id }}</p>
        </div>
        <div class="text-md-end">
            <div class="small text-muted text-uppercase fw-semibold">Voucher no.</div>
            <div class="voucher-no">{{ $entry->getVoucherNumber() }}</div>
        </div>
    </div>

    <table class="voucher-meta">
        <tr>
            <th>Date</th>
            <td>{{ $entry->entry_date->format('d M Y') }}</td>
        </tr>
        <tr>
            <th>Payment</th>
            <td>{{ $entry->type === 'cash_in' ? 'Payment in' : 'Payment out' }}</td>
        </tr>
        <tr>
            <th>Settlement</th>
            <td>{{ $entry->getSettlementLabel() }}</td>
        </tr>
        {{-- Paid by temporarily hidden from daybook UI
        @if($entry->getPaidByLabel() !== '—')
        <tr>
            <th>Paid by</th>
            <td>{{ $entry->getPaidByLabel() }}</td>
        </tr>
        @endif
        --}}
        <tr>
            <th>Paid to / received from</th>
            <td>{{ $entry->getLinkLabel() }}</td>
        </tr>
        @if($entry->getPurchaseFileLabel() !== '—')
        <tr>
            <th>Sale file</th>
            <td>{{ $entry->getPurchaseFileLabel() }}</td>
        </tr>
        @endif
        @if($entry->getSoldAreaLabel() !== '—')
        <tr>
            <th>Sold area</th>
            <td>{{ $entry->getSoldAreaLabel() }}</td>
        </tr>
        @endif
        @if($entry->getPartySubCategoryLabel() !== '—')
        <tr>
            <th>Party sub category</th>
            <td>{{ $entry->getPartySubCategoryLabel() }}</td>
        </tr>
        @endif
        <tr>
            <th>Description</th>
            <td>{{ $entry->description ?: '—' }}</td>
        </tr>
    </table>

    <div class="amount-box">
        <div class="label">Amount (Rs)</div>
        <div class="value">{{ number_format((float) $entry->amount, 2) }}</div>
    </div>

    <div class="signatures">
        <div class="line">Prepared by</div>
        <div class="line">Authorized by</div>
    </div>
</article>
@endsection
