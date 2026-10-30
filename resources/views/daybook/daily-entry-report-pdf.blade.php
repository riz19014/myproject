<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Daily entry report — {{ $from }} to {{ $to }}</title>
    <style>
        @include('pdf.partials.page-setup')
        * { box-sizing: border-box; }
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 10px;
            color: #0f172a;
            margin: 0;
        }
        h1 {
            font-size: 18px;
            font-weight: bold;
            margin: 0 0 4px 0;
            color: #000;
        }
        .meta {
            font-size: 10px;
            color: #64748b;
            margin-bottom: 12px;
        }
        .summary-wrap {
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            background: #f8fafc;
            padding: 10px;
            margin-top: 14px;
        }
        .summary-table {
            width: 100%;
            border-collapse: collapse;
        }
        .summary-table td {
            width: 33.33%;
            text-align: center;
            vertical-align: top;
            padding: 4px 6px;
        }
        .summary-table .lbl {
            font-weight: bold;
            display: block;
            margin-bottom: 3px;
        }
        .c-green { color: #15803d; }
        .c-red { color: #b91c1c; }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid #e2e8f0;
        }
        .data-table th {
            background: #1e3a5f;
            color: #fff;
            font-weight: bold;
            padding: 7px 8px;
            text-align: left;
            border: 1px solid #1e3a5f;
        }
        .data-table th.amt,
        .data-table td.amt {
            text-align: right;
        }
        .data-table td {
            padding: 6px 8px;
            border: 1px solid #e2e8f0;
            vertical-align: top;
        }
        .data-table tr:nth-child(even) td {
            background: #f8fafc;
        }
        .muted { color: #64748b; }
        @include('pdf.partials.company-header-styles')
    </style>
</head>
<body>
    @include('pdf.partials.company-header')
    <h1>Daily entry report</h1>
    <div class="meta">
        Generated on {{ $generatedAt->format('j M Y, g:i A') }}
        · Filtered by {{ $filterBy === 'created_at' ? 'created_at' : 'entry_date' }}
        · {{ \Carbon\Carbon::parse($from)->format('j M Y') }} — {{ \Carbon\Carbon::parse($to)->format('j M Y') }}
        · {{ $entries->count() }} record{{ $entries->count() === 1 ? '' : 's' }}
    </div>

    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 4%;">#</th>
                <th style="width: 9%;">Date</th>
                <th style="width: 12%;">Created at</th>
                <th style="width: 9%;">Voucher</th>
                <th style="width: 20%;">Description</th>
                <th style="width: 15%;">Linked to</th>
                <th style="width: 9%;">Payment</th>
                <th class="amt" style="width: 11%;">Amount</th>
                <th class="amt" style="width: 11%;">Balance</th>
            </tr>
        </thead>
        <tbody>
            @forelse($entries as $e)
                @php
                    $isCashIn = $e->type === \App\Models\DayBookEntry::TYPE_CASH_IN;
                    $createdAtPk = $e->created_at
                        ? $e->created_at->copy()->timezone('Asia/Karachi')
                        : null;
                    $runningBalance = (float) ($e->running_balance ?? 0);
                @endphp
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td>{{ $e->entry_date->format('j M Y') }}</td>
                    <td class="muted">{{ $createdAtPk ? $createdAtPk->format('j M Y, h:i A') : '—' }}</td>
                    <td>{{ $e->getVoucherNumber() }}</td>
                    <td>{{ $e->description ?: '—' }}</td>
                    <td>{{ $e->getLinkLabel() }}</td>
                    <td>{{ $isCashIn ? 'Payment in' : 'Payment out' }}</td>
                    <td class="amt {{ $isCashIn ? 'c-green' : 'c-red' }}">
                        {{ $isCashIn ? '+' : '−' }}{{ number_format($e->amount, 0) }}
                    </td>
                    <td class="amt">
                        {{ $runningBalance >= 0 ? '' : '−' }}{{ number_format(abs($runningBalance), 0) }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" style="text-align: center; padding: 16px;" class="muted">No records in this range.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="summary-wrap">
        <table class="summary-table">
            <tr>
                <td>
                    <span class="lbl">Payment in</span>
                    <span class="c-green">Rs {{ number_format($cashIn, 0) }}</span>
                </td>
                <td>
                    <span class="lbl">Payment out</span>
                    <span class="c-red">Rs {{ number_format($cashOut, 0) }}</span>
                </td>
                <td>
                    <span class="lbl">Net</span>
                    <span>{{ ($cashIn - $cashOut) >= 0 ? '+' : '−' }}Rs {{ number_format(abs($cashIn - $cashOut), 0) }}</span>
                </td>
            </tr>
        </table>
    </div>
</body>
</html>
