<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Ledger — {{ $project->name }}</title>
    <style>
        * { box-sizing: border-box; }
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 9.5pt;
            color: #111111;
            margin: 16mm 14mm 18mm 14mm;
            line-height: 1.35;
        }
        h1 {
            font-size: 15pt;
            font-weight: bold;
            margin: 0 0 2mm 0;
            padding-bottom: 3mm;
            border-bottom: 1pt solid #000000;
            letter-spacing: 0.02em;
        }
        .project-title {
            font-size: 11pt;
            font-weight: bold;
            margin: 4mm 0 1mm 0;
        }
        .land-line {
            font-size: 10pt;
            margin: 0 0 3mm 0;
        }
        .land-line .lbl { font-weight: bold; }
        .meta-block {
            font-size: 8.5pt;
            color: #333333;
            margin-bottom: 5mm;
            line-height: 1.45;
        }
        .meta-block strong { color: #000000; font-weight: bold; }

        .ledger {
            width: 100%;
            border-collapse: collapse;
            font-size: 8.5pt;
            margin-bottom: 5mm;
        }
        .ledger th {
            border: 0.5pt solid #000000;
            background: #eeeeee;
            padding: 2.5mm 2.5mm;
            text-align: left;
            font-weight: bold;
            vertical-align: bottom;
        }
        .ledger th.amt { text-align: right; }
        .ledger td {
            border: 0.5pt solid #333333;
            padding: 2mm 2.5mm;
            vertical-align: top;
        }
        .ledger td.amt {
            text-align: right;
            font-family: DejaVu Sans Mono, DejaVu Sans, sans-serif;
            white-space: nowrap;
        }
        .ledger thead { display: table-header-group; }
        .ledger tbody tr { page-break-inside: avoid; }

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
            text-align: center;
        }
    </style>
</head>
<body>
    <h1>Ledger</h1>
    <div class="project-title">{{ $project->name }}</div>
    <div class="land-line"><span class="lbl">Project land (A — K — M — SQFT):</span> {{ $projectLandAkms }}</div>

    <div class="meta-block">
        @if($project->landType)
            <div><strong>Land type:</strong> {{ $project->landType->name }}</div>
        @endif
        @if($projectTotalBookAmount !== null)
            <div><strong>Total project amount (book):</strong> Rs {{ number_format($projectTotalBookAmount, 2) }}</div>
        @endif
        <div><strong>Generated:</strong> {{ $generatedAt->format('j F Y, g:i A') }} &nbsp;|&nbsp; <strong>Lines:</strong> {{ $entryCount }} &nbsp;|&nbsp; {{ config('app.name') }}</div>
    </div>

    @if(count($ledgerFlatRows) === 0)
        <p class="empty-note">No daybook entries are linked to this project yet.</p>
    @else
        <table class="ledger">
            <thead>
                <tr>
                    <th style="width:11%;">Date</th>
                    <th style="width:18%;">Party</th>
                    <th style="width:36%;">Payment</th>
                    <th class="amt" style="width:15%;">Amount paid</th>
                    <th class="amt" style="width:20%;">Balance amount</th>
                </tr>
            </thead>
            <tbody>
                @foreach($ledgerFlatRows as $row)
                    <tr>
                        <td>{{ $row['date'] }}</td>
                        <td>{{ $row['party'] }}</td>
                        <td>{{ $row['payment'] }}</td>
                        <td class="amt">{{ $row['amount_in'] ? '+' : '−' }}Rs {{ number_format($row['amount'], 2) }}</td>
                        <td class="amt">Rs {{ number_format($row['running'], 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <div class="footer-note">
        Balance amount is the cumulative position for this project after each line, in date order.<br>
        Party column shows the linked party name, or &quot;General&quot; for entries without a party link.
    </div>
</body>
</html>
