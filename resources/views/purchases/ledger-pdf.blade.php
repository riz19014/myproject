<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Purchase land ledger — {{ config('app.name') }}</title>
    <style>
        * { box-sizing: border-box; }
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 9pt;
            color: #111111;
            margin: 14mm 12mm 16mm 12mm;
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
        .meta-block {
            font-size: 8pt;
            color: #333333;
            margin: 3mm 0 5mm 0;
            line-height: 1.45;
        }
        .meta-block strong { color: #000000; font-weight: bold; }

        .project-section {
            margin-bottom: 6mm;
            page-break-inside: avoid;
        }
        .project-section + .project-section {
            page-break-before: always;
        }
        .project-head {
            font-size: 11pt;
            font-weight: bold;
            margin: 0 0 2mm 0;
        }
        .project-summary {
            font-size: 8.5pt;
            margin: 0 0 3mm 0;
            padding: 2.5mm 3mm;
            border: 0.5pt solid #333333;
            background: #f5f5f5;
            line-height: 1.5;
        }
        .project-summary strong { font-weight: bold; color: #000; }

        .ledger {
            width: 100%;
            border-collapse: collapse;
            font-size: 8pt;
            margin-bottom: 2mm;
        }
        .ledger th {
            border: 0.5pt solid #000000;
            background: #eeeeee;
            padding: 2mm 2mm;
            text-align: left;
            font-weight: bold;
            vertical-align: bottom;
        }
        .ledger th.amt { text-align: right; }
        .ledger th.cen { text-align: center; }
        .ledger td {
            border: 0.5pt solid #333333;
            padding: 1.8mm 2mm;
            vertical-align: top;
        }
        .ledger td.amt {
            text-align: right;
            font-family: DejaVu Sans Mono, DejaVu Sans, sans-serif;
            white-space: nowrap;
        }
        .ledger td.cen { text-align: center; }
        .ledger td.opening td { background: #fafafa; }
        .ledger tbody tr { page-break-inside: avoid; }
        tr.opening td {
            background: #fafafa;
            font-weight: bold;
        }
        .small { font-size: 7.5pt; color: #333333; }

        .empty-note {
            font-size: 9pt;
            color: #444444;
            padding: 4mm 0;
        }
        .footer-note {
            margin-top: 5mm;
            padding-top: 3mm;
            border-top: 0.5pt solid #666666;
            font-size: 7.5pt;
            color: #444444;
            text-align: left;
        }
    </style>
</head>
<body>
    <h1>Purchase land ledger</h1>
    <div class="meta-block">
        <div><strong>{{ config('app.name') }}</strong></div>
        <div><strong>Generated:</strong> {{ $generatedAt->format('j F Y, g:i A') }}</div>
        <div><strong>Purchase projects:</strong> {{ $projectCount }} &nbsp;|&nbsp; <strong>Daybook lines (excl. opening rows):</strong> {{ $totalDaybookLines }}</div>
    </div>

    @if($projectCount === 0)
        <p class="empty-note">No purchase-type projects exist yet.</p>
    @else
        @foreach($sections as $section)
            @php
                /** @var \App\Models\Project $proj */
                $proj = $section['project'];
            @endphp
            <section class="project-section">
                <div class="project-head">{{ e($proj->name) }}</div>
                <div class="project-summary">
                    <div><strong>Project book total (land deal):</strong> Rs {{ number_format($section['book_total'], 2) }}</div>
                    <div><strong>Project land (A — K — M — SQFT):</strong> {{ $section['land_akms'] }}</div>
                    @if($proj->landType)
                        <div><strong>Land type:</strong> {{ e($proj->landType->name) }}</div>
                    @endif
                </div>

                <table class="ledger">
                    <thead>
                        <tr>
                            <th class="cen" style="width:10%;">Date</th>
                            <th style="width:16%;">Party name</th>
                            <th style="width:38%;">Description</th>
                            <th class="amt" style="width:16%;">Paid amount</th>
                            <th class="amt" style="width:20%;">Balance amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($section['rows'] as $row)
                            <tr class="{{ !empty($row['is_opening']) ? 'opening' : '' }}">
                                <td class="cen small">{{ $row['date'] !== '' ? e($row['date']) : '—' }}</td>
                                <td>{{ e($row['party']) }}</td>
                                <td class="small">{{ e($row['description']) }}</td>
                                <td class="amt">{{ e($row['paid_display']) }}</td>
                                <td class="amt">Rs {{ number_format($row['balance'], 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </section>
        @endforeach
    @endif

    <div class="footer-note">
        Dates use day-month-year (e.g. 05-Mar-26). The first row is the opening balance from the project book total. Each daybook line linked to this project (with optional party) follows in date order: <strong>Payment out</strong> is treated as an amount paid toward the land deal and reduces the balance; <strong>Payment in</strong> increases the balance again. Paid amount and settlement (cash, cheque, pay order, bank, reference) come from daybook entries. Balance amount is the remaining amount on the project after each line.
    </div>
</body>
</html>
