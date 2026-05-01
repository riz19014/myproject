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
            margin: 22px 28px 32px 28px;
        }
        h1 {
            font-size: 17px;
            font-weight: bold;
            margin: 0 0 6px 0;
            color: #0c1929;
            letter-spacing: 0.02em;
        }
        .meta {
            font-size: 9px;
            color: #64748b;
            margin-bottom: 10px;
            line-height: 1.45;
        }
        .party-line {
            font-size: 9px;
            color: #334155;
            margin-bottom: 8px;
        }
        .totals-line {
            font-size: 9px;
            margin-bottom: 14px;
            padding: 8px 10px;
            background: #f1f5f9;
            border: 1px solid #cbd5e1;
            border-radius: 4px;
        }
        .totals-line .in { color: #15803d; font-weight: bold; }
        .totals-line .out { color: #b91c1c; font-weight: bold; }

        .ledger-table {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid #1e3a5f;
            font-size: 9px;
        }
        .ledger-table thead th {
            background: #1e3a5f;
            color: #fff;
            font-weight: bold;
            padding: 8px 10px;
            text-align: left;
            border: 1px solid #1e3a5f;
        }
        .ledger-table thead th.amt { text-align: right; }
        .ledger-table tbody td {
            padding: 6px 10px;
            border: 1px solid #cbd5e1;
            vertical-align: top;
        }
        .ledger-table tbody td.amt { text-align: right; font-family: DejaVu Sans, sans-serif; }
        .ledger-table tbody tr.meta td {
            background: #f8fafc;
            color: #475569;
            font-style: italic;
        }
        .ledger-table tbody tr:nth-child(even):not(.meta) td {
            background: #fafafa;
        }

        .ledger-table tfoot td.ledger-footer-totals {
            text-align: right;
            font-size: 9px;
            line-height: 1.55;
            background: #f8fafc;
            border: 1px solid #cbd5e1;
            padding: 8px 10px;
            vertical-align: top;
        }
        .ledger-table tfoot td.ledger-footer-spacer {
            border: none;
            background: transparent;
        }
        .ledger-table tfoot .ledger-footer-line + .ledger-footer-line {
            margin-top: 2px;
        }

        .signatures {
            margin-top: 48px;
            width: 100%;
        }
        .signatures table {
            width: 100%;
            border-collapse: collapse;
        }
        .signatures td {
            width: 42%;
            vertical-align: top;
            text-align: center;
            padding: 0 8px;
        }
        .signatures td.spacer { width: 16%; }
        .sig-line {
            border-top: 1px solid #0f172a;
            margin: 0 auto 8px auto;
            width: 88%;
            height: 1px;
        }
        .sig-title {
            font-size: 10px;
            font-weight: bold;
            color: #0f172a;
            margin-bottom: 2px;
        }
        .sig-hint {
            font-size: 8px;
            color: #64748b;
        }
    </style>
</head>
<body>
    <h1>Daybook ledger</h1>
    <div class="meta">
        Period: <strong>{{ $from->format('j M Y') }}</strong> to <strong>{{ $to->format('j M Y') }}</strong>
        · Generated {{ $generatedAt->format('j M Y, g:i A') }}
    </div>
    @if(!empty($selectedParty))
        <div class="party-line"><strong>Party:</strong> {{ $selectedParty->name }} (linked entries only)</div>
    @endif

    @if($grandCashIn > 0 || $grandCashOut > 0 || $openingBalanceSummary != 0.0)
        <div class="totals-line">
            @if($grandCashIn > 0)
                <span class="in">Payment in (range): Rs {{ number_format($grandCashIn, 0) }}</span>
            @endif
            @if($grandCashIn > 0 && ($grandCashOut > 0 || $openingBalanceSummary != 0.0))
                &nbsp;&nbsp;·&nbsp;&nbsp;
            @endif
            @if($grandCashOut > 0)
                <span class="out">Payment out (range): Rs {{ number_format($grandCashOut, 0) }}</span>
            @endif
            @if($grandCashOut > 0 && $openingBalanceSummary != 0.0)
                &nbsp;&nbsp;·&nbsp;&nbsp;
            @endif
            @if($openingBalanceSummary != 0.0)
                <span style="color:#0f172a;font-weight:bold;">Opening balance: {{ $openingBalanceSummaryDisplay }}</span>
            @endif
        </div>
    @endif

    <table class="ledger-table">
        <thead>
            <tr>
                <th style="width:10%">Date</th>
                <th style="width:13%">Payment</th>
                <th>Description</th>
                <th class="amt" style="width:14%">Amount (Rs.)</th>
                <th class="amt" style="width:14%">Balance (Rs.)</th>
            </tr>
        </thead>
        <tbody>
            @forelse($ledgerRows as $r)
                <tr @if(!empty($r['is_meta'])) class="meta" @endif>
                    <td>{{ $r['date'] }}</td>
                    <td>{{ $r['payment'] }}</td>
                    <td>{{ $r['description'] }}</td>
                    <td class="amt">{{ $r['amount'] }}</td>
                    <td class="amt">{{ $r['balance_display'] }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" style="text-align:center;color:#64748b;padding:16px;">
                        {{ !empty($selectedParty) ? 'No lines for this party in this range.' : 'No rows for this range.' }}
                    </td>
                </tr>
            @endforelse
        </tbody>
        @if(count($ledgerFooter))
            <tfoot>
                <tr>
                    <td colspan="3" class="ledger-footer-spacer"></td>
                    <td colspan="2" class="ledger-footer-totals">
                        @foreach($ledgerFooter as $line)
                            <div class="ledger-footer-line"><strong>{{ $line['label'] }}:</strong> {{ $line['value'] }}</div>
                        @endforeach
                    </td>
                </tr>
            </tfoot>
        @endif
    </table>

    <div class="signatures">
        <table>
            <tr>
                <td>
                    <div class="sig-line"></div>
                    <div class="sig-title">Accountant</div>
                    <div class="sig-hint">Name &amp; signature</div>
                </td>
                <td class="spacer"></td>
                <td>
                    <div class="sig-line"></div>
                    <div class="sig-title">Chief Executive Officer (CEO)</div>
                    <div class="sig-hint">Name &amp; signature</div>
                </td>
            </tr>
        </table>
    </div>
</body>
</html>
