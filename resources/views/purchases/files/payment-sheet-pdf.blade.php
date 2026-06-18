<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Payment sheet — {{ $purchaseFile->file_name }}</title>
    <style>
        @page { margin: 12mm 10mm 14mm 10mm; }
        * { box-sizing: border-box; }
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 9pt;
            color: #111;
            margin: 0;
            line-height: 1.35;
        }
        h1 {
            font-size: 14pt;
            font-weight: bold;
            margin: 0 0 1mm 0;
        }
        .meta {
            font-size: 8.5pt;
            color: #333;
            margin: 0 0 4mm 0;
            line-height: 1.45;
        }
        .meta strong { color: #000; }
        .summary {
            width: 100%;
            border-collapse: collapse;
            font-size: 8.5pt;
            margin-bottom: 4mm;
        }
        .summary td {
            border: 0.5pt solid #333;
            padding: 2mm 2.5mm;
            width: 33.33%;
            vertical-align: top;
        }
        .summary .lbl {
            display: block;
            font-size: 7.5pt;
            color: #555;
            margin-bottom: 0.5mm;
        }
        .summary .val {
            font-weight: bold;
        }
        .section-title {
            font-size: 10pt;
            font-weight: bold;
            margin: 3mm 0 1.5mm 0;
        }
        .section-sub {
            font-size: 8pt;
            color: #444;
            margin: 0 0 2mm 0;
        }
        table.sheet {
            width: 100%;
            border-collapse: collapse;
            font-size: 8pt;
            margin-bottom: 3mm;
        }
        table.sheet th {
            border: 0.5pt solid #000;
            background: #eee;
            padding: 2mm 2mm;
            text-align: left;
            font-weight: bold;
            vertical-align: bottom;
        }
        table.sheet th.amt,
        table.sheet td.amt {
            text-align: right;
            white-space: nowrap;
        }
        table.sheet td {
            border: 0.5pt solid #333;
            padding: 1.8mm 2mm;
            vertical-align: top;
        }
        table.sheet tr.opening td {
            background: #f5f5f5;
            font-weight: bold;
        }
        table.sheet tr.footer td {
            background: #f0f0f0;
            font-weight: bold;
        }
        .empty-note {
            font-size: 8.5pt;
            color: #444;
            margin: 0 0 3mm 0;
        }
        .footer-note {
            margin-top: 4mm;
            padding-top: 2.5mm;
            border-top: 0.5pt solid #666;
            font-size: 7.5pt;
            color: #444;
        }
    </style>
</head>
<body>
    <h1>Payment sheet</h1>
    <div class="meta">
        <div><strong>File:</strong> {{ $purchaseFile->file_name }}</div>
        <div><strong>File date:</strong> {{ $purchaseFile->file_date?->format('d M Y') ?? '—' }}</div>
        <div><strong>Project:</strong> {{ $purchaseFile->project?->name ?? '—' }}</div>
        @if($purchaseFile->dealers->isNotEmpty())
            <div><strong>Dealers:</strong> {{ $purchaseFile->dealers->pluck('name')->implode(', ') }}</div>
        @endif
        <div><strong>Generated:</strong> {{ $generatedAt->format('d M Y, g:i A') }}</div>
    </div>

    <table class="summary">
        <tr>
            <td>
                <span class="lbl">Land total (file)</span>
                <span class="val">Rs {{ number_format($landTotalRs, 2) }}</span>
                @if($landAreaLabel !== '—')
                    <span class="lbl" style="margin-top:1mm;">{{ $landAreaLabel }}</span>
                @endif
            </td>
            <td>
                <span class="lbl">Total paid (DayBook)</span>
                <span class="val">Rs {{ number_format($totalPaid, 2) }}</span>
            </td>
            <td>
                <span class="lbl">Balance payable</span>
                <span class="val">
                    @if($balancePayable >= 0)
                        Rs {{ number_format($balancePayable, 2) }}
                    @else
                        Overpaid Rs {{ number_format(abs($balancePayable), 2) }}
                    @endif
                </span>
            </td>
        </tr>
    </table>

    <div class="section-title">Sellers on file</div>
    @if($sellers->isEmpty())
        <p class="empty-note">No sellers on this file yet.</p>
    @else
        <table class="sheet">
            <thead>
                <tr>
                    <th style="width:5%;">#</th>
                    <th style="width:22%;">Party</th>
                    <th style="width:14%;">Mouza</th>
                    <th style="width:14%;">Khasra</th>
                    <th style="width:16%;">Area</th>
                    <th class="amt" style="width:12%;">Rs / acre</th>
                    <th class="amt" style="width:17%;">Land total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($sellers as $seller)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td>{{ $seller->party?->name ?? '—' }}</td>
                        <td>{{ $seller->moza ?: '—' }}</td>
                        <td>{{ $seller->khasra ?: '—' }}</td>
                        <td>{{ \App\Support\LandMeasure::formatAkmsLabelFromMarla((float) $seller->land_area_marla) }}</td>
                        <td class="amt">{{ number_format((float) $seller->amount_per_acre, 0) }}</td>
                        <td class="amt">Rs {{ number_format((float) $seller->line_total_rs, 2) }}</td>
                    </tr>
                @endforeach
                <tr class="footer">
                    <td colspan="4" class="amt">Total</td>
                    <td>{{ $landAreaLabel }}</td>
                    <td></td>
                    <td class="amt">Rs {{ number_format($landTotalRs, 2) }}</td>
                </tr>
            </tbody>
        </table>
    @endif

    <div class="section-title">Payments</div>
    <div class="section-sub">DayBook payment lines linked to this purchase file ({{ $paymentEntryCount }} {{ $paymentEntryCount === 1 ? 'entry' : 'entries' }}).</div>

    @if($paymentEntryCount === 0)
        <p class="empty-note">No payments recorded for this file yet. Link daybook entries to this file when recording payments.</p>
    @else
        <table class="sheet">
            <thead>
                <tr>
                    <th style="width:12%;">Date</th>
                    <th style="width:16%;">Party</th>
                    <th style="width:28%;">Description</th>
                    <th style="width:22%;">Payment</th>
                    <th class="amt" style="width:10%;">Amount</th>
                    <th class="amt" style="width:12%;">Balance payable</th>
                </tr>
            </thead>
            <tbody>
                <tr class="opening">
                    <td>—</td>
                    <td>—</td>
                    <td>Land total (file)</td>
                    <td>—</td>
                    <td class="amt">—</td>
                    <td class="amt">Rs {{ number_format($landTotalRs, 2) }}</td>
                </tr>
                @foreach($paymentLines as $line)
                    <tr>
                        <td>{{ $line['date'] }}</td>
                        <td>{{ $line['party'] }}</td>
                        <td>{{ $line['description'] }}</td>
                        <td>{{ $line['payment'] }}</td>
                        <td class="amt">{{ $line['amount_display'] }}</td>
                        <td class="amt">
                            @if($line['balance'] >= 0)
                                Rs {{ number_format($line['balance'], 2) }}
                            @else
                                Overpaid Rs {{ number_format(abs($line['balance']), 2) }}
                            @endif
                        </td>
                    </tr>
                @endforeach
                <tr class="footer">
                    <td colspan="5" class="amt">Balance payable</td>
                    <td class="amt">
                        @if($balancePayable >= 0)
                            Rs {{ number_format($balancePayable, 2) }}
                        @else
                            Overpaid Rs {{ number_format(abs($balancePayable), 2) }}
                        @endif
                    </td>
                </tr>
            </tbody>
        </table>
    @endif

    <div class="footer-note">
        Payment out reduces the balance payable; payment in increases it. Only daybook lines with this purchase file selected are included.
    </div>
</body>
</html>
