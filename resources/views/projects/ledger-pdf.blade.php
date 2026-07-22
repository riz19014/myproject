<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Ledger — {{ $project->name }}</title>
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
        h1 {
            font-size: 14pt;
            font-weight: bold;
            margin: 0 0 1mm 0;
        }
        .project-name {
            font-size: 11pt;
            font-weight: bold;
            margin: 0 0 4mm 0;
        }
        .summary {
            width: 100%;
            border-collapse: collapse;
            font-size: 8.5pt;
            margin-bottom: 5mm;
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
        .section {
            margin-bottom: 5mm;
            page-break-inside: avoid;
        }
        .section-title {
            font-size: 10pt;
            font-weight: bold;
            margin: 0 0 0.5mm 0;
        }
        .section-sub {
            font-size: 8pt;
            color: #444;
            margin: 0 0 2mm 0;
        }
        .ledger {
            width: 100%;
            border-collapse: collapse;
            font-size: 8.5pt;
        }
        .ledger th {
            border: 0.5pt solid #000;
            background: #eee;
            padding: 2mm 2mm;
            text-align: left;
            font-weight: bold;
        }
        .ledger th.amt { text-align: right; }
        .ledger td {
            border: 0.5pt solid #333;
            padding: 1.8mm 2mm;
            vertical-align: top;
        }
        .ledger td.amt {
            text-align: right;
            white-space: nowrap;
        }
        .ledger tr.opening td { background: #f5f5f5; }
        .ledger tr.footer td {
            background: #f0f0f0;
            font-weight: bold;
        }
        .empty-note {
            font-size: 9pt;
            color: #444;
        }
        @include('pdf.partials.company-header-styles')
    </style>
</head>
<body>
    @include('pdf.partials.company-header')
    <h1>Ledger</h1>
    <div class="project-name">{{ $project->labeledName() }}</div>

    @if($entries->isEmpty())
        <p class="empty-note">No daybook entries linked to this project.</p>
    @else
        <table class="summary">
            <tr>
                <td>
                    <span class="lbl">Total land (files)</span>
                    <span class="val">Rs {{ number_format($ledgerProjectLandTotalRs, 2) }}</span>
                </td>
                <td>
                    <span class="lbl">Total paid (DayBook)</span>
                    <span class="val">Rs {{ number_format($ledgerTotalPaid, 2) }}</span>
                </td>
                <td>
                    <span class="lbl">Balance payable</span>
                    <span class="val">
                        @if($ledgerTotalPayable >= 0)
                            Rs {{ number_format($ledgerTotalPayable, 2) }}
                        @else
                            Overpaid Rs {{ number_format(abs($ledgerTotalPayable), 2) }}
                        @endif
                    </span>
                </td>
            </tr>
        </table>

        @foreach($ledgerSections as $section)
            @php
                $hasLand = ($section['land_total_rs'] ?? 0) > 0;
                $sectionPayable = $section['payable'] ?? null;
                $sectionOverpaid = $hasLand && $sectionPayable !== null && $sectionPayable < 0;
            @endphp
            <div class="section">
                <div class="section-title">{{ $section['heading'] }}</div>
                @if(!empty($section['subtitle']))
                    <div class="section-sub">{{ $section['subtitle'] }}</div>
                @endif

                <table class="ledger">
                    <thead>
                        <tr>
                            <th style="width:14%;">Date</th>
                            <th style="width:38%;">Description</th>
                            <th style="width:18%;">Payment</th>
                            <th class="amt" style="width:15%;">Amount</th>
                            <th class="amt" style="width:15%;">{{ $hasLand ? 'Balance payable' : 'Paid so far' }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if($hasLand)
                            <tr class="opening">
                                <td>—</td>
                                <td>Land total (file)</td>
                                <td>—</td>
                                <td class="amt">—</td>
                                <td class="amt">Rs {{ number_format($section['land_total_rs'], 2) }}</td>
                            </tr>
                        @endif
                        @foreach($section['lines'] as $row)
                            @php
                                $e = $row['entry'];
                                $rowPayable = $row['payable'];
                                $rowOverpaid = $hasLand && $rowPayable !== null && $rowPayable < 0;
                            @endphp
                            <tr>
                                <td>{{ $e->entry_date->format('d M Y') }}</td>
                                <td>{{ $e->description ?: '—' }}</td>
                                <td>{{ $e->type === 'cash_in' ? 'Payment in' : 'Payment out' }}</td>
                                <td class="amt">Rs {{ number_format((float) $e->amount, 2) }}</td>
                                <td class="amt">
                                    @if($rowPayable === null)
                                        Rs {{ number_format($row['paid'], 2) }}
                                    @elseif($rowOverpaid)
                                        Overpaid Rs {{ number_format(abs($rowPayable), 2) }}
                                    @else
                                        Rs {{ number_format($rowPayable, 2) }}
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                        <tr class="footer">
                            <td colspan="4" class="amt" style="text-align:right;">
                                @if($hasLand)
                                    Balance payable — {{ $section['heading'] }}
                                @else
                                    Total paid — {{ $section['heading'] }}
                                @endif
                            </td>
                            <td class="amt">
                                @if($hasLand)
                                    @if($sectionOverpaid)
                                        Overpaid Rs {{ number_format(abs($sectionPayable), 2) }}
                                    @else
                                        Rs {{ number_format($sectionPayable, 2) }}
                                    @endif
                                @else
                                    Rs {{ number_format($section['total_paid'], 2) }}
                                @endif
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        @endforeach
    @endif
</body>
</html>
