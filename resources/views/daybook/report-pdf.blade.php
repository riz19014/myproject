<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Daybook Report — {{ $day->format('j M Y') }}</title>
    <style>
        * { box-sizing: border-box; }
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
            color: #0f172a;
            margin: 24px 32px;
        }
        h1 {
            font-size: 22px;
            font-weight: bold;
            margin: 0 0 6px 0;
            color: #000;
        }
        .generated {
            font-size: 10px;
            color: #64748b;
            margin-bottom: 20px;
        }
        .summary-wrap {
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            background: #f8fafc;
            padding: 14px 12px;
            margin-bottom: 20px;
        }
        .summary-table {
            width: 100%;
            border-collapse: collapse;
        }
        .summary-table td {
            width: 20%;
            text-align: center;
            vertical-align: top;
            padding: 4px 6px;
        }
        .summary-table .lbl {
            font-weight: bold;
            color: #0f172a;
            display: block;
            margin-bottom: 4px;
        }
        .summary-table .val { font-size: 12px; }
        .c-green { color: #15803d; }
        .c-red { color: #b91c1c; }
        .c-black { color: #0f172a; }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid #e2e8f0;
        }
        .data-table th {
            background: #1e3a5f;
            color: #fff;
            font-weight: bold;
            padding: 10px 12px;
            text-align: left;
            border: 1px solid #1e3a5f;
        }
        .data-table th.amt, .data-table td.amt {
            text-align: right;
        }
        .data-table td {
            padding: 8px 12px;
            border: 1px solid #e2e8f0;
            vertical-align: middle;
        }
        .row-date {
            background: #dbeafe;
            font-weight: bold;
            color: #0f172a;
        }
        .row-date td {
            border-color: #bfdbfe;
        }
        .row-opening td {
            color: #64748b;
            font-style: italic;
        }
        .row-opening .amt {
            font-style: italic;
        }
        .row-closing td {
            font-weight: bold;
            border-top: 2px solid #e2e8f0;
        }
        .row-closing .balance-cell {
            color: #15803d;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <h1>Daybook Report</h1>
    <div class="generated">Generated on {{ $generatedAt->format('j M Y, g:i A') }}</div>
    <div class="generated" style="margin-bottom: 14px;">Previous day ({{ $prevDay->format('l, j M Y') }}) closing: <strong>Rs {{ number_format($previousDayClosing, 0) }}</strong></div>

    <div class="summary-wrap">
        <table class="summary-table">
            <tr>
                <td>
                    <span class="lbl">Opening balance</span>
                    <span class="val c-black">Rs {{ number_format($openingAmount, 0) }}</span>
                </td>
                <td>
                    <span class="lbl">Petty cash</span>
                    <span class="val c-black">Rs {{ number_format($pettyCashAmount, 0) }}</span>
                </td>
                <td>
                    <span class="lbl">Payment in</span>
                    <span class="val c-green">Rs {{ number_format($cashIn, 0) }}</span>
                </td>
                <td>
                    <span class="lbl">Payment out</span>
                    <span class="val c-red">Rs {{ number_format($cashOut, 0) }}</span>
                </td>
                <td>
                    <span class="lbl">Net Balance</span>
                    <span class="val c-green">Rs {{ number_format($closingBalance, 0) }}</span>
                </td>
            </tr>
        </table>
    </div>

    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 38%;">Description</th>
                <th style="width: 18%;">Payment</th>
                <th class="amt" style="width: 22%;">Amount</th>
                <th class="amt" style="width: 22%;">Balance</th>
            </tr>
        </thead>
        <tbody>
            <tr class="row-date">
                <td colspan="4">{{ $day->format('l') }}, {{ $day->format('j M Y') }}</td>
            </tr>
            <tr class="row-opening">
                <td>Opening balance (carried)</td>
                <td></td>
                <td class="amt"></td>
                <td class="amt balance-cell">Rs {{ number_format($openingAmount, 0) }}</td>
            </tr>
            <tr class="row-opening">
                <td>Petty cash</td>
                <td></td>
                <td class="amt">Rs {{ number_format($pettyCashAmount, 0) }}</td>
                <td class="amt balance-cell">Rs {{ number_format($openingAmount + $pettyCashAmount, 0) }}</td>
            </tr>
            @foreach($tableRows as $row)
                <tr>
                    <td>{{ $row['description'] }}</td>
                    <td>{{ $row['type_label'] }}</td>
                    <td class="amt">{{ $row['amount_str'] }}</td>
                    <td class="amt">Rs {{ number_format($row['balance'], 0) }}</td>
                </tr>
            @endforeach
            <tr class="row-closing">
                <td>Closing Balance</td>
                <td></td>
                <td class="amt"></td>
                <td class="amt balance-cell">Rs {{ number_format($closingBalance, 0) }}</td>
            </tr>
        </tbody>
    </table>
</body>
</html>
