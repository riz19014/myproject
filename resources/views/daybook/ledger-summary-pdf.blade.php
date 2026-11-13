<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Ledger summary — {{ $selectedParty->name }}</title>
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
            font-size: 17px;
            font-weight: bold;
            margin: 0 0 6px 0;
            color: #0c1929;
            letter-spacing: 0.02em;
        }
        h2 {
            font-size: 12px;
            font-weight: bold;
            margin: 0 0 6px 0;
            color: #0c1929;
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
            margin-bottom: 12px;
        }
        .project-section {
            margin-bottom: 22px;
            page-break-inside: avoid;
        }
        .project-section + .project-section {
            page-break-before: auto;
            border-top: 1px solid #cbd5e1;
            padding-top: 14px;
        }
        .totals-line {
            font-size: 9px;
            margin-bottom: 10px;
            padding: 6px 8px;
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
            padding: 7px 8px;
            text-align: left;
            border: 1px solid #1e3a5f;
        }
        .ledger-table thead th.amt { text-align: right; }
        .ledger-table tbody td {
            padding: 5px 8px;
            border: 1px solid #cbd5e1;
            vertical-align: top;
        }
        .ledger-table tbody td.amt { text-align: right; font-family: DejaVu Sans, sans-serif; }
        .ledger-table tbody tr:nth-child(even) td { background: #fafafa; }
        .ledger-table tfoot td.ledger-footer-totals {
            text-align: right;
            font-size: 9px;
            line-height: 1.55;
            background: #f8fafc;
            border: 1px solid #cbd5e1;
            padding: 7px 8px;
            vertical-align: top;
        }
        .ledger-table tfoot td.ledger-footer-spacer {
            border: none;
            background: transparent;
        }
        .ledger-table tfoot .ledger-footer-line + .ledger-footer-line {
            margin-top: 2px;
        }
        .empty-note {
            text-align: center;
            color: #64748b;
            padding: 14px;
        }
        .signatures {
            margin-top: 40px;
            width: 100%;
        }
        .signatures table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }
        .signatures td {
            width: 33.33%;
            vertical-align: top;
            text-align: center;
            padding: 0 6px;
        }
        .sig-line {
            border-top: 1px solid #0f172a;
            margin: 0 auto 8px auto;
            width: 92%;
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
        @include('pdf.partials.company-header-styles')
    </style>
</head>
<body>
    @include('pdf.partials.company-header')
    <h1>Ledger summary{{ !empty($print_all) ? ' — all projects' : '' }}</h1>
    <div class="meta">
        Period: <strong>{{ $from->format('j M Y') }}</strong> to <strong>{{ $to->format('j M Y') }}</strong>
        · Generated {{ $generatedAt->format('j M Y, g:i A') }}
    </div>
    <div class="party-line"><strong>Party:</strong> {{ $selectedParty->name }}</div>

    @forelse($sections as $section)
        @php
            $summary = $section['summary'];
            $ledgerRows = $section['ledgerRows'];
            $grandCashIn = $section['grandCashIn'];
            $grandCashOut = $section['grandCashOut'];
            $ledgerFooter = $section['ledgerFooter'];
        @endphp
        <div class="project-section">
            <h2>
                Project:
                @if((int) $summary['project_id'] === 0)
                    No project
                @else
                    {{ $summary['project_name'] }}
                    @if(!empty($summary['land_type']))
                        ({{ $summary['land_type'] }})
                    @endif
                    @if(!empty($summary['project_status_label']))
                        — {{ $summary['project_status_label'] }}
                    @endif
                @endif
            </h2>

            @if($grandCashIn > 0 || $grandCashOut > 0)
                <div class="totals-line">
                    @if($grandCashIn > 0)
                        <span class="in">Payment in: Rs {{ number_format($grandCashIn, 0) }}</span>
                    @endif
                    @if($grandCashIn > 0 && $grandCashOut > 0)
                        &nbsp;&nbsp;·&nbsp;&nbsp;
                    @endif
                    @if($grandCashOut > 0)
                        <span class="out">Payment out: Rs {{ number_format($grandCashOut, 0) }}</span>
                    @endif
                </div>
            @endif

            <table class="ledger-table">
                <thead>
                    <tr>
                        <th style="width:8%">Date</th>
                        <th style="width:8%">Voucher</th>
                        <th style="width:9%">Payment</th>
                        <th style="width:18%">Settlement</th>
                        <th>Description</th>
                        <th class="amt" style="width:11%">Amount (Rs.)</th>
                        <th class="amt" style="width:11%">Balance (Rs.)</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($ledgerRows as $r)
                        <tr>
                            <td>{{ $r['date'] }}</td>
                            <td style="font-family:DejaVu Sans,sans-serif;">{{ !empty($r['voucher']) ? $r['voucher'] : '—' }}</td>
                            <td>{{ $r['payment'] }}</td>
                            <td style="font-size:8px;">{{ $r['settlement'] ?? '—' }}</td>
                            <td>
                                {{ $r['description'] }}
                                @if(!empty($r['description_tags']))
                                    <div style="margin-top:2px;font-size:7.5px;color:#64748b;line-height:1.35;">
                                        @foreach($r['description_tags'] as $tag)
                                            <span style="display:inline-block;margin:1px 3px 1px 0;padding:1px 4px;border:1px solid #e2e8f0;border-radius:2px;background:#f8fafc;">
                                                <strong>{{ $tag['label'] }}:</strong> {{ $tag['value'] }}
                                            </span>
                                        @endforeach
                                    </div>
                                @endif
                            </td>
                            <td class="amt">{{ $r['amount'] }}</td>
                            <td class="amt">{{ $r['balance_display'] }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="empty-note">No entries for this project.</td>
                        </tr>
                    @endforelse
                </tbody>
                @if(count($ledgerFooter))
                <tfoot>
                    <tr>
                        <td colspan="5" class="ledger-footer-spacer"></td>
                        <td colspan="2" class="ledger-footer-totals">
                            @foreach($ledgerFooter as $line)
                                <div class="ledger-footer-line"><strong>{{ $line['label'] }}:</strong> {{ $line['value'] }}</div>
                            @endforeach
                        </td>
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>
    @empty
        <p class="empty-note">No projects found for this party.</p>
    @endforelse

    <div class="signatures">
        <table>
            <tr>
                <td>
                    <div class="sig-line"></div>
                    <div class="sig-title">Accountant</div>
                    <div class="sig-hint">Name &amp; signature</div>
                </td>
                <td>
                    <div class="sig-line"></div>
                    <div class="sig-title">Party signature</div>
                    <div class="sig-hint">Name &amp; signature</div>
                </td>
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
