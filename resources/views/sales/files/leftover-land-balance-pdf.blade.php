<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Leftover land balance — {{ $project->name }}</title>
    <style>
        @page { margin: 5mm 6mm 5mm 6mm; size: A4 landscape; }
        * { box-sizing: border-box; }
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 7.2pt;
            color: #111;
            margin: 0;
            line-height: 1.25;
        }

        .pdf-co-header-wrap { margin: 0 0 2mm 0; }
        .pdf-co-header-table { width: 100%; border-collapse: collapse; }
        .pdf-co-header-table > tbody > tr > td { vertical-align: middle; padding: 0; border: 0; }
        .pdf-co-brand-cell { width: 70%; }
        .pdf-co-brand-inner { border-collapse: collapse; }
        .pdf-co-brand-inner td { vertical-align: middle; padding: 0; border: 0; }
        .pdf-co-logo-cell { width: 18mm; padding-right: 1.5mm; }
        .pdf-co-logo-img { display: block; width: 16mm; max-width: 16mm; height: auto; max-height: 14mm; }
        .pdf-co-logo-fallback { font-size: 11pt; font-weight: bold; color: #2b78c5; }
        .pdf-co-name-primary {
            font-family: DejaVu Serif, serif;
            font-size: 11pt; font-weight: bold; color: #2b78c5; line-height: 1.05;
        }
        .pdf-co-name-secondary {
            font-size: 7.5pt; font-weight: bold; color: #111; margin-top: 0.3mm;
        }
        .pdf-co-contact-cell {
            width: 30%; text-align: right; font-size: 6.5pt; line-height: 1.3;
            border-left: 0.5pt solid #111; padding-left: 2.5mm;
        }
        .pdf-co-contact-name { font-size: 7.5pt; font-weight: bold; color: #2b78c5; margin-bottom: 0.4mm; }
        .pdf-co-contact-email { color: #2b78c5; }
        .pdf-co-address-bar {
            margin-top: 1mm; background: #2b78c5; color: #fff; text-align: center;
            font-size: 6.5pt; font-weight: bold; padding: 1mm 2mm;
        }

        .top-line {
            width: 100%;
            border-collapse: collapse;
            margin: 0 0 2mm 0;
        }
        .top-line td { border: 0; padding: 0; vertical-align: bottom; }
        .doc-title {
            font-family: DejaVu Serif, serif;
            font-size: 9.5pt;
            font-weight: bold;
        }
        .project-line { font-size: 7pt; color: #555; }
        .project-line strong { color: #111; }
        .generated { font-size: 6pt; color: #8899aa; text-align: right; }

        .summary {
            width: 100%;
            border-collapse: collapse;
            margin: 0 0 2mm 0;
            font-size: 7pt;
        }
        .summary th,
        .summary td {
            border: 0.4pt solid #b8c4d0;
            padding: 1.2mm 1.8mm;
            vertical-align: middle;
        }
        .summary th {
            background: #dfe8f1;
            font-size: 6.2pt;
            text-transform: uppercase;
            letter-spacing: 0.03em;
            color: #445566;
            white-space: nowrap;
            width: 18%;
        }
        .summary td { background: #f8fafc; }
        .summary .land {
            font-family: DejaVu Serif, serif;
            font-weight: bold;
            color: #047857;
            font-size: 8pt;
        }
        .summary .muted { color: #667788; font-size: 6.5pt; }
        .chips { white-space: nowrap; }
        .chip {
            display: inline-block;
            background: #eef2ff;
            color: #3730a3;
            font-size: 6.2pt;
            font-weight: bold;
            padding: 0.4mm 1.2mm;
            margin-right: 0.8mm;
        }

        .balance {
            width: 100%;
            border-collapse: collapse;
            margin: 0 0 2mm 0;
            font-size: 7pt;
        }
        .balance td {
            border: 0.4pt solid #c5d0dc;
            padding: 1.2mm 2mm;
            width: 33.33%;
        }
        .balance .lbl {
            display: block;
            font-size: 5.8pt;
            font-weight: bold;
            text-transform: uppercase;
            color: #64748b;
            margin-bottom: 0.3mm;
        }
        .balance .val {
            font-family: DejaVu Serif, serif;
            font-weight: bold;
            font-size: 8pt;
        }
        .balance .sold { background: #fff7ed; }
        .balance .left { background: #ecfdf5; }
        .balance .total { background: #f8fafc; }

        .section {
            font-family: DejaVu Serif, serif;
            font-size: 8pt;
            font-weight: bold;
            margin: 0 0 1mm 0;
        }

        table.data {
            width: 100%;
            border-collapse: collapse;
            font-size: 6.5pt;
            margin: 0 0 2mm 0;
        }
        table.data th {
            border: 0.35pt solid #8fa0b2;
            background: #dfe8f1;
            padding: 0.9mm 1mm;
            text-align: left;
            font-weight: bold;
            white-space: nowrap;
        }
        table.data th.num,
        table.data td.num { text-align: right; white-space: nowrap; }
        table.data td {
            border: 0.3pt solid #c5d0dc;
            padding: 0.8mm 1mm;
            vertical-align: middle;
        }
        table.data tr.alt td { background: #f7fafc; }
        table.data .sub {
            display: block;
            font-size: 5.5pt;
            color: #667788;
            font-weight: normal;
        }
        table.data .left-val { font-weight: bold; color: #047857; }
        table.data tr.total td {
            background: #e4ecf4;
            font-weight: bold;
            border-top: 0.7pt solid #8fa0b2;
        }

        .empty { color: #667788; font-size: 8pt; }
    </style>
</head>
<body>
@php
    $company = $pdfCompany ?? null;
    $nameLines = $company?->pdfHeaderNameLines() ?? ['', ''];
    $phoneLines = collect(preg_split('/\s*,\s*/', (string) ($company?->phone ?? '')) ?: [])
        ->map(static fn ($line) => trim($line))
        ->filter()
        ->values();
    $logoDataUri = $company?->pdfLogoDataUri();
    $initials = $company?->pdfLogoInitials() ?? '';
    $leftoverColumns = $leftoverColumns ?? [];
    $leftoverFiles = $leftoverFiles ?? [];
    $leftoverTotals = $leftoverTotals ?? [];
    $totalPlotChips = [];
    foreach ($leftoverColumns as $column) {
        $plot = ($leftoverTotals['formula_remaining'] ?? [])[$column['plot_key'] ?? ''] ?? null;
        if (! $plot) {
            continue;
        }
        $totalPlotChips[] = ($column['column_code'] ?? '').' '.$plot['remaining_display'];
    }
@endphp

<div class="pdf-co-header-wrap">
    <table class="pdf-co-header-table">
        <tr>
            <td class="pdf-co-brand-cell">
                <table class="pdf-co-brand-inner">
                    <tr>
                        <td class="pdf-co-logo-cell">
                            @if($logoDataUri)
                                <img src="{{ $logoDataUri }}" alt="" class="pdf-co-logo-img">
                            @elseif($initials !== '')
                                <div class="pdf-co-logo-fallback">{{ $initials }}</div>
                            @endif
                        </td>
                        <td>
                            @if($nameLines[0] !== '')
                                <div class="pdf-co-name-primary">{{ $nameLines[0] }}</div>
                            @endif
                            @if($nameLines[1] !== '')
                                <div class="pdf-co-name-secondary">{{ $nameLines[1] }}</div>
                            @endif
                        </td>
                    </tr>
                </table>
            </td>
            <td class="pdf-co-contact-cell">
                <div class="pdf-co-contact-name">Contact Us</div>
                @foreach($phoneLines as $phoneLine)
                    <div>{{ $phoneLine }}</div>
                @endforeach
                @if(filled($company?->email))
                    <div class="pdf-co-contact-email">{{ $company->email }}</div>
                @endif
            </td>
        </tr>
    </table>
    @if(filled($company?->address))
        <div class="pdf-co-address-bar">{{ $company->address }}</div>
    @endif
</div>

<table class="top-line">
    <tr>
        <td>
            <div class="doc-title">Leftover land balance{{ !empty($pdfSubtitle) ? ' — '.$pdfSubtitle : '' }}</div>
            <div class="project-line">Project: <strong>{{ $project->labeledName() }}</strong></div>
        </td>
        <td class="generated">{{ $generatedAt->format('d M Y, h:i A') }}</td>
    </tr>
</table>

@if($leftoverFiles === [])
    <p class="empty">No moved sale land files to show leftover for.</p>
@else
    <table class="summary">
        <tr>
            <th>Total Land</th>
            <td class="land">{{ $leftoverTotals['total_land'] ?? '—' }}</td>
            <th>Files</th>
            <td>{{ (int) ($leftoverTotals['files_count'] ?? 0) }}</td>
            <th>Khasras</th>
            <td>{{ (int) ($leftoverTotals['total_khasras'] ?? 0) }}</td>
        </tr>
        <tr>
            <th>Mouzas</th>
            <td colspan="3">
                {{ (int) ($leftoverTotals['mouzas_count'] ?? 0) }}
                @if(($leftoverTotals['mouzas_names'] ?? '—') !== '—')
                    <span class="muted">({{ $leftoverTotals['mouzas_names'] }})</span>
                @endif
            </td>
            <th>Plots left</th>
            <td class="chips">
                @forelse($totalPlotChips as $chip)
                    <span class="chip">{{ $chip }}</span>
                @empty
                    —
                @endforelse
            </td>
        </tr>
    </table>

    <table class="balance">
        <tr>
            <td class="total">
                <span class="lbl">Total</span>
                <span class="val">{{ $leftoverTotals['total_land'] ?? '—' }}</span>
            </td>
            <td class="sold">
                <span class="lbl">Sold</span>
                <span class="val">{{ $leftoverTotals['sold_land'] ?? '—' }}</span>
            </td>
            <td class="left">
                <span class="lbl">Leftover</span>
                <span class="val">{{ $leftoverTotals['remaining_land'] ?? '—' }}</span>
            </td>
        </tr>
    </table>

    @if($leftoverColumns !== [])
        <div class="section">Plot files left</div>
        <table class="data">
            <thead>
                <tr>
                    <th>Plot</th>
                    <th class="num">Available</th>
                    <th class="num">Sold</th>
                    <th class="num">Left</th>
                </tr>
            </thead>
            <tbody>
                @foreach($leftoverColumns as $column)
                    @php
                        $plot = ($leftoverTotals['formula_remaining'] ?? [])[$column['plot_key'] ?? ''] ?? null;
                    @endphp
                    <tr @class(['alt' => $loop->even])>
                        <td>
                            <strong>{{ $column['column_code'] ?? '' }}</strong>
                            <span class="sub">{{ $column['short_label'] ?? $column['plot_label'] ?? '' }}</span>
                        </td>
                        <td class="num">{{ $plot['available_display'] ?? '—' }}</td>
                        <td class="num">{{ $plot['sold_display'] ?? '—' }}</td>
                        <td class="num left-val">{{ $plot['remaining_display'] ?? '—' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <div class="section">Files breakdown</div>
    <table class="data">
        <thead>
            <tr>
                <th>#</th>
                <th>File</th>
                <th>Mouza</th>
                <th class="num">Khasras</th>
                <th>Total</th>
                <th>Sold</th>
                <th>Left</th>
                <th>Status</th>
                @foreach($leftoverColumns as $column)
                    <th class="num">{{ $column['column_code'] ?? '' }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach($leftoverFiles as $file)
                <tr @class(['alt' => $loop->even])>
                    <td class="num">{{ $loop->iteration }}</td>
                    <td><strong>{{ $file['file_name'] }}</strong></td>
                    <td>{{ $file['moza'] }}</td>
                    <td class="num">{{ (int) ($file['items_count'] ?? 0) }}</td>
                    <td>{{ $file['total_land'] }}</td>
                    <td>{{ $file['sold_land'] }}</td>
                    <td class="left-val">{{ $file['remaining_land'] }}</td>
                    <td>{{ $file['status'] ?? 'Available' }}</td>
                    @foreach($leftoverColumns as $column)
                        @php
                            $plot = ($file['plots'] ?? [])[$column['plot_key'] ?? ''] ?? null;
                        @endphp
                        <td class="num">{{ $plot['remaining_display'] ?? '—' }}</td>
                    @endforeach
                </tr>
            @endforeach
            <tr class="total">
                <td colspan="4" style="text-align:right;">Total</td>
                <td>{{ $leftoverTotals['total_land'] ?? '—' }}</td>
                <td>{{ $leftoverTotals['sold_land'] ?? '—' }}</td>
                <td class="left-val">{{ $leftoverTotals['remaining_land'] ?? '—' }}</td>
                <td></td>
                @foreach($leftoverColumns as $column)
                    @php
                        $plot = ($leftoverTotals['formula_remaining'] ?? [])[$column['plot_key'] ?? ''] ?? null;
                    @endphp
                    <td class="num">{{ $plot['remaining_display'] ?? '—' }}</td>
                @endforeach
            </tr>
        </tbody>
    </table>
@endif
</body>
</html>
