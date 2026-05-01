<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Daybook ledger — {{ $from->format('j M Y') }} to {{ $to->format('j M Y') }}</title>
    <style>
        * { box-sizing: border-box; }
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 10px;
            color: #0f172a;
            margin: 20px 24px;
        }
        h1 {
            font-size: 18px;
            font-weight: bold;
            margin: 0 0 4px 0;
            color: #000;
        }
        .sub {
            font-size: 10px;
            color: #64748b;
            margin-bottom: 12px;
        }
        .generated {
            font-size: 9px;
            color: #64748b;
            margin-bottom: 14px;
        }
        .grand {
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            padding: 10px 12px;
            margin-bottom: 16px;
            background: #f8fafc;
        }
        .grand table { width: 100%; border-collapse: collapse; }
        .grand td { text-align: center; padding: 4px; }
        .grand .lbl { font-weight: bold; display: block; margin-bottom: 2px; }
        .c-green { color: #15803d; }
        .c-red { color: #b91c1c; }

        .day-block {
            margin-bottom: 18px;
            page-break-inside: avoid;
        }
        .day-block h2 {
            font-size: 12px;
            margin: 0 0 6px 0;
            color: #1e3a5f;
            border-bottom: 1px solid #bfdbfe;
            padding-bottom: 4px;
        }
        .day-prev {
            font-size: 9px;
            color: #64748b;
            margin-bottom: 8px;
        }
        .party-open {
            font-size: 9px;
            color: #64748b;
            margin-bottom: 8px;
        }
        .mini-sum {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8px;
            font-size: 9px;
        }
        .mini-sum th, .mini-sum td {
            border: 1px solid #e2e8f0;
            padding: 4px 6px;
        }
        .mini-sum th { background: #f1f5f9; text-align: left; width: 22%; }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid #e2e8f0;
            font-size: 9px;
        }
        .data-table th {
            background: #1e3a5f;
            color: #fff;
            font-weight: bold;
            padding: 6px 8px;
            text-align: left;
            border: 1px solid #1e3a5f;
        }
        .data-table th.amt, .data-table td.amt { text-align: right; }
        .data-table td {
            padding: 5px 8px;
            border: 1px solid #e2e8f0;
            vertical-align: middle;
        }
        .row-muted td { color: #64748b; font-style: italic; }
        .row-closing td { font-weight: bold; border-top: 2px solid #e2e8f0; }
        .row-closing .balance-cell { color: #15803d; }
    </style>
</head>
<body>
    <h1>Daybook ledger</h1>
    <div class="sub">{{ $from->format('l, j M Y') }} — {{ $to->format('l, j M Y') }}</div>
    @if(!empty($selectedParty))
        <div class="sub" style="margin-top:-8px;"><strong>Party:</strong> {{ $selectedParty->name }} (linked lines only; cumulative balance)</div>
    @endif
    <div class="generated">Generated on {{ $generatedAt->format('j M Y, g:i A') }}</div>

    <div class="grand">
        <table>
            <tr>
                <td>
                    <span class="lbl">Payment in (range){{ !empty($selectedParty) ? ' · party' : '' }}</span>
                    <span class="c-green">Rs {{ number_format($grandCashIn, 0) }}</span>
                </td>
                <td>
                    <span class="lbl">Payment out (range){{ !empty($selectedParty) ? ' · party' : '' }}</span>
                    <span class="c-red">Rs {{ number_format($grandCashOut, 0) }}</span>
                </td>
            </tr>
        </table>
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
            $partyFilter = ! empty($L['party_filter']);
            $partyRunningOpen = (float) ($L['party_running_open'] ?? 0);
        @endphp
        <div class="day-block">
            <h2>{{ $day->format('l, j M Y') }}</h2>

            @if($partyFilter)
                @if($partyRunningOpen != 0.0)
                    <div class="party-open">Cumulative balance at start of day: <strong>Rs {{ number_format($partyRunningOpen, 0) }}</strong></div>
                @endif
                <table class="mini-sum">
                    <tr>
                        <th>Payment in (day)</th><td class="c-green">Rs {{ number_format($cashIn, 0) }}</td>
                        <th>Payment out (day)</th><td class="c-red">Rs {{ number_format($cashOut, 0) }}</td>
                    </tr>
                    <tr>
                        <th>Closing (cumulative)</th><td colspan="3" class="c-green"><strong>Rs {{ number_format($closingBalance, 0) }}</strong></td>
                    </tr>
                </table>
            @else
                <div class="day-prev">Previous day ({{ $prevDay->format('l, j M Y') }}) closing: <strong>Rs {{ number_format($previousDayClosing, 0) }}</strong></div>

                <table class="mini-sum">
                    <tr>
                        <th>Opening</th><td>Rs {{ number_format($openingAmount, 0) }}</td>
                        <th>Petty</th><td>Rs {{ number_format($pettyCashAmount, 0) }}</td>
                    </tr>
                    <tr>
                        <th>Payment in</th><td class="c-green">Rs {{ number_format($cashIn, 0) }}</td>
                        <th>Payment out</th><td class="c-red">Rs {{ number_format($cashOut, 0) }}</td>
                    </tr>
                    <tr>
                        <th>Closing</th><td colspan="3" class="c-green"><strong>Rs {{ number_format($closingBalance, 0) }}</strong></td>
                    </tr>
                </table>
            @endif

            <table class="data-table">
                <thead>
                    <tr>
                        <th style="width:38%">Description</th>
                        <th style="width:18%">Payment</th>
                        <th class="amt" style="width:22%">Amount</th>
                        <th class="amt" style="width:22%">Balance</th>
                    </tr>
                </thead>
                <tbody>
                    @if($partyFilter)
                        @if($partyRunningOpen != 0.0)
                            <tr class="row-muted">
                                <td colspan="2">Brought forward</td>
                                <td class="amt"></td>
                                <td class="amt balance-cell">Rs {{ number_format($partyRunningOpen, 0) }}</td>
                            </tr>
                        @endif
                    @else
                        <tr class="row-muted">
                            <td colspan="2">Opening balance (carried)</td>
                            <td class="amt"></td>
                            <td class="amt balance-cell">Rs {{ number_format($openingAmount, 0) }}</td>
                        </tr>
                        <tr class="row-muted">
                            <td colspan="2">Petty cash</td>
                            <td class="amt">Rs {{ number_format($pettyCashAmount, 0) }}</td>
                            <td class="amt balance-cell">Rs {{ number_format($openingAmount + $pettyCashAmount, 0) }}</td>
                        </tr>
                    @endif
                    @foreach($tableRows as $row)
                        <tr>
                            <td>{{ $row['description'] }}</td>
                            <td>{{ $row['type_label'] }}</td>
                            <td class="amt">{{ $row['amount_str'] }}</td>
                            <td class="amt">Rs {{ number_format($row['balance'], 0) }}</td>
                        </tr>
                    @endforeach
                    <tr class="row-closing">
                        <td colspan="2">{{ $partyFilter ? 'Closing (cumulative)' : 'Closing balance' }}</td>
                        <td class="amt"></td>
                        <td class="amt balance-cell">Rs {{ number_format($closingBalance, 0) }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    @empty
        <p style="color:#64748b;">{{ !empty($selectedParty) ? 'No daybook lines linked to this party in this date range.' : 'No ledger rows for this range.' }}</p>
    @endforelse
</body>
</html>
