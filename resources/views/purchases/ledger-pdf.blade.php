<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Purchase land ledger — {{ config('app.name') }}</title>
    <style>
        * { box-sizing: border-box; }
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 8.5pt;
            color: #111111;
            margin: 12mm 12mm 14mm 12mm;
            line-height: 1.35;
        }
        h1 {
            font-size: 14pt;
            font-weight: bold;
            margin: 0 0 2mm 0;
            padding-bottom: 2.5mm;
            border-bottom: 1pt solid #000000;
            letter-spacing: 0.02em;
        }
        .subtitle {
            font-size: 10pt;
            font-weight: bold;
            margin: 3mm 0 2mm 0;
        }
        .meta-block {
            font-size: 8pt;
            color: #333333;
            margin-bottom: 4mm;
            line-height: 1.45;
        }
        .meta-block strong { color: #000000; font-weight: bold; }

        .ledger {
            width: 100%;
            border-collapse: collapse;
            font-size: 7.5pt;
            margin-bottom: 4mm;
        }
        .ledger th {
            border: 0.5pt solid #000000;
            background: #eeeeee;
            padding: 2mm 1.8mm;
            text-align: left;
            font-weight: bold;
            vertical-align: bottom;
        }
        .ledger th.amt { text-align: right; }
        .ledger th.cen { text-align: center; }
        .ledger td {
            border: 0.5pt solid #333333;
            padding: 1.5mm 1.8mm;
            vertical-align: top;
        }
        .ledger td.amt {
            text-align: right;
            font-family: DejaVu Sans Mono, DejaVu Sans, sans-serif;
            white-space: nowrap;
        }
        .ledger td.cen { text-align: center; }
        .ledger thead { display: table-header-group; }
        .ledger tbody tr { page-break-inside: avoid; }
        .ledger tfoot td {
            border: 0.5pt solid #000000;
            background: #e8e8e8;
            font-weight: bold;
            padding: 2mm 1.8mm;
        }
        .ledger tfoot td.amt { text-align: right; font-family: DejaVu Sans Mono, DejaVu Sans, sans-serif; }

        .empty-note {
            font-size: 9pt;
            color: #444444;
            padding: 4mm 0;
        }
        .footer-note {
            margin-top: 4mm;
            padding-top: 2.5mm;
            border-top: 0.5pt solid #666666;
            font-size: 7pt;
            color: #444444;
            text-align: center;
        }
        .small { font-size: 7pt; color: #333333; }
    </style>
</head>
<body>
    <h1>Purchase land ledger</h1>
    <div class="subtitle">All purchase-type lines (chronological)</div>

    <div class="meta-block">
        <div><strong>{{ config('app.name') }}</strong></div>
        <div><strong>Generated:</strong> {{ $generatedAt->format('j F Y, g:i A') }} &nbsp;|&nbsp; <strong>Lines:</strong> {{ $purchaseLineCount }}</div>
        @if($purchaseLineCount > 0)
            <div><strong>Total area:</strong> {{ \App\Support\LandMeasure::formatAkmsLabelFromMarla($purchaseTotalMarla) }} <span class="small">({{ \App\Support\LandMeasure::formatMarlaTotal($purchaseTotalMarla) }})</span></div>
            <div><strong>Total amount paid:</strong> Rs {{ number_format($purchaseTotalRs, 2) }}</div>
        @endif
    </div>

    @if($purchaseItems->isEmpty())
        <p class="empty-note">No purchase lines to include in this ledger.</p>
    @else
        @php $pdfRunningRs = 0.0; @endphp
        <table class="ledger">
            <thead>
                <tr>
                    <th style="width:8%;" class="cen">Date</th>
                    <th style="width:3%;" class="cen">#</th>
                    <th style="width:14%;">Project</th>
                    <th style="width:14%;">Party</th>
                    <th style="width:8%;">Moza</th>
                    <th style="width:8%;">Khasra</th>
                    <th style="width:18%;">Area</th>
                    <th class="amt" style="width:8%;">Rs / acre</th>
                    <th class="amt" style="width:10%;">Amount paid</th>
                    <th class="amt" style="width:11%;">Balance amount</th>
                </tr>
            </thead>
            <tbody>
                @foreach($purchaseItems as $item)
                    @php $pdfRunningRs += (float) $item->line_total_rs; @endphp
                    <tr>
                        <td class="cen small">{{ $item->created_at?->format('Y-m-d') }}</td>
                        <td class="cen">{{ $loop->iteration }}</td>
                        <td>{{ e($item->project?->name ?? '—') }}</td>
                        <td>{{ e($item->party?->name ?? '—') }}</td>
                        <td class="small">{{ e($item->moza ?? '') ?: '—' }}</td>
                        <td class="small">{{ e($item->khasra ?? '') ?: '—' }}</td>
                        <td class="small">{{ \App\Support\LandMeasure::formatAkmsLabelFromMarla((float) $item->land_area_marla) }}</td>
                        <td class="amt">{{ number_format((float) $item->amount_per_acre, 0) }}</td>
                        <td class="amt">Rs {{ number_format((float) $item->line_total_rs, 2) }}</td>
                        <td class="amt">Rs {{ number_format($pdfRunningRs, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="6" style="text-align: right;">Totals</td>
                    <td class="small">{{ \App\Support\LandMeasure::formatAkmsLabelFromMarla($purchaseTotalMarla) }}</td>
                    <td class="amt" style="color: #333;">—</td>
                    <td class="amt">Rs {{ number_format($purchaseTotalRs, 2) }}</td>
                    <td class="amt">Rs {{ number_format($purchaseTotalRs, 2) }}</td>
                </tr>
            </tfoot>
        </table>
    @endif

    <div class="footer-note">
        Rows are in date order (then by line id). Balance amount is the running total of amount paid after each line.
    </div>
</body>
</html>
