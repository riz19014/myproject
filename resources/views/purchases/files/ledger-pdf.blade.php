<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Purchase ledger</title>
    <style>
        @include('pdf.partials.page-setup')
        * { box-sizing: border-box; }
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 9pt;
            color: #111;
            margin: 0;
            line-height: 1.35;
        }
        @include('pdf.partials.company-header-styles')
        table.ledger {
            width: 100%;
            border-collapse: collapse;
            font-size: 8pt;
            margin-bottom: 1mm;
        }
        table.ledger th {
            border: 0.5pt solid #000;
            background: #eee;
            padding: 1.5mm 2mm;
            text-align: left;
            font-weight: bold;
            vertical-align: middle;
        }
        table.ledger th .ledger-th-line {
            display: block;
            line-height: 1.15;
        }
        table.ledger th.amt,
        table.ledger td.amt {
            text-align: right;
            white-space: nowrap;
        }
        table.ledger td {
            border: 0.5pt solid #333;
            padding: 1.8mm 2mm;
            vertical-align: top;
        }
        table.ledger td.payment-cell {
            font-size: 7pt;
            line-height: 1.2;
            padding: 1.2mm 1.5mm;
        }
        .payment-detail span {
            display: block;
        }
        .payment-detail__method {
            font-weight: bold;
        }
        .payment-detail__meta {
            color: #222;
        }
        .payment-detail__desc {
            color: #444;
            font-size: 6.5pt;
        }
        table.ledger tr.opening td { background: #f5f5f5; }
        table.ledger tr.footer td {
            background: #f0f0f0;
            font-weight: bold;
        }
        .empty-note {
            font-size: 8.5pt;
            color: #444;
            margin: 0 0 3mm 0;
        }
        .ledger-file-summary {
            width: 100%;
            border-collapse: collapse;
            margin: 0 0 2mm 0;
            font-size: 7.5pt;
            line-height: 1.25;
            color: #222;
        }
        .ledger-file-summary td {
            padding: 0;
            border: 0;
            vertical-align: top;
        }
        .ledger-file-summary__left {
            width: 58%;
            padding-right: 3mm;
        }
        .ledger-file-summary__right {
            width: 42%;
        }
        .ledger-file-summary__headline {
            font-size: 9pt;
            font-weight: bold;
            margin-bottom: 0.5mm;
        }
        .ledger-file-summary__line {
            color: #333;
        }
        .ledger-file-summary__land-label {
            font-weight: bold;
            font-size: 7pt;
            color: #444;
        }
        .ledger-file-summary__land-rs {
            font-weight: bold;
            font-size: 8.5pt;
            margin-top: 0.2mm;
        }
        .ledger-file-summary__land-area {
            font-size: 7pt;
            color: #444;
            margin-top: 0.2mm;
        }
        .ledger-file-summary__meta-row {
            padding-top: 0.6mm;
        }
        .ledger-file-summary__meta-table {
            width: 100%;
            border-collapse: collapse;
        }
        .ledger-file-summary__meta-table td {
            padding: 0;
            border: 0;
            vertical-align: top;
        }
        .ledger-file-summary__meta-cell {
            width: 50%;
            padding-right: 2mm;
            color: #333;
        }
        .ledger-file-summary__meta-cell:last-child {
            padding-right: 0;
            padding-left: 2mm;
        }
        .ledger-file-summary__meta-label {
            font-weight: bold;
            color: #444;
        }
        table.ledger {
            margin-top: 1mm;
        }
    </style>
</head>
<body>
@php
    $pfPdfAmount = static function ($value): string {
        if ($value === null || $value === '') {
            return '—';
        }

        return number_format((float) $value, 0);
    };
@endphp

@include('pdf.partials.company-header')

@if(count($ledgerSectionsOrdered) === 0)
    <p class="empty-note">No ledger entries.</p>
@else
    @foreach($ledgerSectionsOrdered as $block)
        @php
            $section = $block['section'];
            $footer = $section['footer'] ?? [];
        @endphp
        @if(!empty($ledgerPdfSummary))
            @include('purchases.files.partials.ledger-pdf-summary', ['summary' => $ledgerPdfSummary])
        @endif
        <table class="ledger">
            <thead>
                <tr>
                    <th style="width:6%;">
                        <span class="ledger-th-line">#Sr</span>
                        <span class="ledger-th-line">&nbsp;</span>
                    </th>
                    <th style="width:11%;">
                        <span class="ledger-th-line">Date</span>
                        <span class="ledger-th-line">&nbsp;</span>
                    </th>
                    <th style="width:10%;">
                        <span class="ledger-th-line">#Voucher</span>
                        <span class="ledger-th-line">&nbsp;</span>
                    </th>
                    <th>
                        <span class="ledger-th-line">Party</span>
                        <span class="ledger-th-line">&nbsp;</span>
                    </th>
                    <th style="width:14%;">
                        <span class="ledger-th-line">Payment</span>
                        <span class="ledger-th-line">Method</span>
                    </th>
                    <th class="amt" style="width:13%;">
                        <span class="ledger-th-line">Debit</span>
                        <span class="ledger-th-line">(Payable)</span>
                    </th>
                    <th class="amt" style="width:13%;">
                        <span class="ledger-th-line">Credit</span>
                        <span class="ledger-th-line">(Paid)</span>
                    </th>
                    <th class="amt" style="width:14%;">
                        <span class="ledger-th-line">Running</span>
                        <span class="ledger-th-line">Balance</span>
                    </th>
                </tr>
            </thead>
            <tbody>
                @forelse($section['rows'] ?? [] as $row)
                    <tr class="{{ !empty($row['is_opening']) ? 'opening' : '' }}">
                        <td>{{ $loop->iteration }}</td>
                        <td>{{ $row['date'] ?? '—' }}</td>
                        <td>{{ $row['voucher'] ?? '—' }}</td>
                        <td>{{ $row['party'] ?? '—' }}</td>
                        <td class="payment-cell">
                            @php($paymentLines = $row['payment_method_lines'] ?? [])
                            @if(count($paymentLines) === 0)
                                —
                            @else
                                <div class="payment-detail">
                                    @foreach($paymentLines as $line)
                                        <span class="payment-detail__{{ $line['kind'] ?? 'meta' }}">{{ $line['text'] ?? '' }}</span>
                                    @endforeach
                                </div>
                            @endif
                        </td>
                        <td class="amt">{{ $pfPdfAmount($row['debit'] ?? null) }}</td>
                        <td class="amt">{{ $pfPdfAmount($row['credit'] ?? null) }}</td>
                        <td class="amt">{{ $pfPdfAmount($row['running_balance'] ?? null) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="empty-note">No ledger entries.</td>
                    </tr>
                @endforelse
            </tbody>
            <tfoot>
                <tr class="footer">
                    <td colspan="5"></td>
                    <td class="amt">{{ $pfPdfAmount($footer['debit'] ?? null) }}</td>
                    <td class="amt">{{ $pfPdfAmount($footer['credit'] ?? null) }}</td>
                    <td class="amt">{{ $pfPdfAmount($footer['running_balance'] ?? null) }}</td>
                </tr>
            </tfoot>
        </table>
    @endforeach
@endif

@include('purchases.files.partials.signature-details', [
    'signatureDetails' => $signatureDetails,
])
</body>
</html>
