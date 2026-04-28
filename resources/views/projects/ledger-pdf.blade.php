<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Project Ledger — {{ $project->name }}</title>
    <style>
        * { box-sizing: border-box; }
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 10px;
            color: #0f172a;
            margin: 22px 28px 32px;
            line-height: 1.35;
        }
        .doc-header {
            border-bottom: 3px solid #1e3a5f;
            padding-bottom: 12px;
            margin-bottom: 16px;
        }
        .doc-title {
            font-size: 20px;
            font-weight: bold;
            color: #1e3a5f;
            margin: 0 0 4px 0;
            letter-spacing: 0.02em;
        }
        .project-name {
            font-size: 15px;
            font-weight: bold;
            color: #0f172a;
            margin: 0 0 6px 0;
        }
        .meta {
            font-size: 9px;
            color: #64748b;
        }
        .meta strong { color: #475569; }

        .summary-bar {
            width: 100%;
            border-collapse: separate;
            border-spacing: 8px 0;
            margin-bottom: 18px;
        }
        .summary-bar td {
            width: 33.33%;
            vertical-align: top;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            padding: 10px 12px;
            background: #f8fafc;
        }
        .summary-bar .lbl {
            font-size: 8px;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: #64748b;
            font-weight: bold;
            margin-bottom: 4px;
        }
        .summary-bar .num {
            font-size: 14px;
            font-weight: bold;
        }
        .c-in { color: #15803d; }
        .c-out { color: #b91c1c; }
        .c-net { color: #1e3a5f; }

        .party-block {
            margin-bottom: 16px;
            page-break-inside: avoid;
        }
        .party-head {
            background: linear-gradient(90deg, #1e3a5f 0%, #2d4a6f 100%);
            color: #fff;
            padding: 8px 12px;
            border-radius: 4px 4px 0 0;
        }
        .party-head h2 {
            margin: 0;
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }
        .party-sub {
            font-size: 8px;
            opacity: 0.9;
            margin-top: 3px;
            font-weight: normal;
        }
        .ledger-table {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid #e2e8f0;
            border-top: none;
        }
        .ledger-table th {
            background: #f1f5f9;
            color: #334155;
            font-size: 8px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            padding: 6px 8px;
            text-align: left;
            border-bottom: 1px solid #e2e8f0;
        }
        .ledger-table th.amt { text-align: right; }
        .ledger-table td {
            padding: 6px 8px;
            border-bottom: 1px solid #f1f5f9;
            vertical-align: top;
        }
        .ledger-table tr:last-child td { border-bottom: none; }
        .ledger-table td.amt { text-align: right; font-family: DejaVu Sans Mono, DejaVu Sans, sans-serif; white-space: nowrap; }
        .party-foot td {
            background: #fafafa;
            font-weight: bold;
            font-size: 9px;
            border-top: 1px solid #e2e8f0;
        }
        .type-in { color: #15803d; }
        .type-out { color: #b91c1c; }

        .grand-totals {
            margin-top: 20px;
            padding: 12px 14px;
            border: 2px solid #1e3a5f;
            border-radius: 6px;
            background: #f8fafc;
        }
        .grand-totals table {
            width: 100%;
            border-collapse: collapse;
        }
        .grand-totals td {
            padding: 4px 0;
            font-size: 11px;
        }
        .grand-totals .g-label { color: #475569; }
        .grand-totals .g-val { text-align: right; font-weight: bold; }

        .footer-note {
            margin-top: 18px;
            font-size: 8px;
            color: #94a3b8;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="doc-header">
        <div class="doc-title">Project payment ledger</div>
        <div class="project-name">{{ $project->name }}</div>
        <div class="meta">
            Generated <strong>{{ $generatedAt->format('j F Y, g:i A') }}</strong>
            &nbsp;·&nbsp; {{ $entryCount }} line{{ $entryCount === 1 ? '' : 's' }} &nbsp;·&nbsp; {{ config('app.name') }}
        </div>
    </div>

    <table class="summary-bar">
        <tr>
            <td>
                <div class="lbl">Total payment in</div>
                <div class="num c-in">Rs {{ number_format($ledgerTotalIn, 2) }}</div>
            </td>
            <td>
                <div class="lbl">Total payment out</div>
                <div class="num c-out">Rs {{ number_format($ledgerTotalOut, 2) }}</div>
            </td>
            <td>
                <div class="lbl">Net (in − out)</div>
                <div class="num c-net">Rs {{ number_format($ledgerNetFlow, 2) }}</div>
            </td>
        </tr>
    </table>

    @foreach($ledgerSections as $section)
        <div class="party-block">
            <div class="party-head">
                <h2>{{ $section['heading'] }}</h2>
                @if(!empty($section['subtitle']))
                    <div class="party-sub">{{ $section['subtitle'] }}</div>
                @endif
            </div>
            <table class="ledger-table">
                <thead>
                    <tr>
                        <th style="width: 12%;">Date</th>
                        <th style="width: 36%;">Description</th>
                        <th style="width: 14%;">Payment</th>
                        <th class="amt" style="width: 14%;">Amount</th>
                        <th class="amt" style="width: 14%;">Section balance</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($section['lines'] as $row)
                        @php
                            /** @var \App\Models\DayBookEntry $e */
                            $e = $row['entry'];
                            $isIn = $e->type === 'cash_in';
                        @endphp
                        <tr>
                            <td>{{ $e->entry_date->format('d M Y') }}</td>
                            <td>{{ $e->description ?: '—' }}</td>
                            <td class="{{ $isIn ? 'type-in' : 'type-out' }}">{{ $isIn ? 'Payment in' : 'Payment out' }}</td>
                            <td class="amt">{{ $isIn ? '+' : '−' }}Rs {{ number_format((float) $e->amount, 2) }}</td>
                            <td class="amt">Rs {{ number_format($row['balance'], 2) }}</td>
                        </tr>
                    @endforeach
                    <tr class="party-foot">
                        <td colspan="4">Net for this {{ $section['key'] === 'general' ? 'section' : 'party' }}</td>
                        <td class="amt">Rs {{ number_format($section['net'], 2) }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    @endforeach

    @if(count($ledgerSections) === 0)
        <p style="color:#64748b;font-size:11px;">No daybook entries are linked to this project yet.</p>
    @endif

    <div class="grand-totals">
        <table>
            <tr>
                <td class="g-label">Grand total — payment in</td>
                <td class="g-val c-in">Rs {{ number_format($ledgerTotalIn, 2) }}</td>
            </tr>
            <tr>
                <td class="g-label">Grand total — payment out</td>
                <td class="g-val c-out">Rs {{ number_format($ledgerTotalOut, 2) }}</td>
            </tr>
            <tr>
                <td class="g-label">Net position (in − out)</td>
                <td class="g-val c-net">Rs {{ number_format($ledgerNetFlow, 2) }}</td>
            </tr>
        </table>
    </div>

    <div class="footer-note">
        Section balance is a running total within each party (or general) group, in chronological order.<br>
        Grand totals aggregate all entries for this project.
    </div>
</body>
</html>
