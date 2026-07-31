<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Total land — {{ $project->name }}</title>
    <style>
        @page { margin: 8mm 9mm 9mm 9mm; size: A4 landscape; }
        * { box-sizing: border-box; }
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 9pt;
            color: #1a1a1a;
            margin: 0;
            line-height: 1.4;
        }

        /* Company header — keep logo size */
        .pdf-co-header-wrap { margin: 0 0 4mm 0; }
        .pdf-co-header-table { width: 100%; border-collapse: collapse; }
        .pdf-co-header-table > tbody > tr > td { vertical-align: middle; padding: 0; border: 0; }
        .pdf-co-brand-cell { width: 68%; vertical-align: middle; }
        .pdf-co-brand-inner { border-collapse: collapse; }
        .pdf-co-brand-inner td { vertical-align: middle; padding: 0; border: 0; }
        .pdf-co-logo-cell { width: 32mm; padding-right: 2mm; text-align: left; }
        .pdf-co-logo-img { display: block; width: 28mm; max-width: 28mm; height: auto; max-height: 26mm; }
        .pdf-co-logo-fallback {
            font-size: 16pt; font-weight: bold; color: #2b78c5;
            text-align: left; letter-spacing: 0.04em; line-height: 1.1;
        }
        .pdf-co-name-cell { text-align: left; vertical-align: middle; }
        .pdf-co-name-primary {
            font-family: DejaVu Serif, serif;
            font-size: 17pt; font-weight: bold; color: #2b78c5;
            letter-spacing: 0.03em; line-height: 1.05; text-align: left;
        }
        .pdf-co-name-secondary {
            font-family: DejaVu Sans, sans-serif;
            font-size: 11pt; font-weight: bold; color: #111;
            margin-top: 0.8mm; line-height: 1.1; text-align: left;
        }
        .pdf-co-contact-cell {
            width: 32%; text-align: right; font-size: 8.5pt; line-height: 1.45;
            color: #111; border-left: 0.6pt solid #111; padding-left: 4mm; vertical-align: middle;
        }
        .pdf-co-contact-name { font-size: 10pt; font-weight: bold; color: #2b78c5; margin-bottom: 1mm; }
        .pdf-co-contact-email { color: #2b78c5; }
        .pdf-co-address-bar {
            margin-top: 2mm; background: #2b78c5; color: #fff; text-align: center;
            font-size: 8.5pt; font-weight: bold; padding: 2mm 3mm; line-height: 1.35;
        }

        .project-line {
            font-family: DejaVu Sans, sans-serif;
            font-size: 9pt;
            color: #555;
            margin: 0 0 4mm 0;
        }
        .project-line strong {
            font-family: DejaVu Serif, serif;
            color: #111;
            font-size: 10pt;
        }

        .summary {
            width: 100%;
            border-collapse: collapse;
            margin: 0 0 4.5mm 0;
        }
        .summary > tbody > tr > td {
            vertical-align: top;
            border: 0;
            padding: 0;
        }
        .summary .total-cell {
            width: 34%;
            padding-right: 5mm;
        }
        .total-box {
            border: 0.7pt solid #c5d0dc;
            background: #f4f7fb;
            padding: 3.5mm 4mm;
        }
        .total-label {
            font-family: DejaVu Sans, sans-serif;
            font-size: 8pt;
            font-weight: bold;
            color: #5a6a7a;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            margin: 0 0 1.5mm 0;
        }
        .total-value {
            font-family: DejaVu Serif, serif;
            font-size: 13pt;
            font-weight: bold;
            color: #111;
            letter-spacing: 0.01em;
            line-height: 1.25;
        }
        .files-title {
            font-family: DejaVu Serif, serif;
            font-size: 11pt;
            font-weight: bold;
            color: #111;
            margin: 0 0 2mm 0;
        }
        .file-list {
            width: 100%;
            border-collapse: collapse;
        }
        .file-list td {
            padding: 1.4mm 0;
            font-family: DejaVu Sans, sans-serif;
            font-size: 9.5pt;
            color: #111;
            vertical-align: top;
            border-bottom: 0.3pt solid #e8edf2;
        }
        .file-list tr:last-child td { border-bottom: 0; }
        .file-list .num {
            width: 7mm;
            font-family: DejaVu Serif, serif;
            color: #2b78c5;
            font-weight: bold;
            font-size: 9.5pt;
            padding-right: 2mm;
        }

        .section {
            font-family: DejaVu Serif, serif;
            font-size: 11pt;
            font-weight: bold;
            color: #111;
            margin: 0 0 2mm 0;
            padding-top: 2.5mm;
            border-top: 0.7pt solid #c5d0dc;
        }

        table.detail {
            width: 100%;
            border-collapse: collapse;
            font-family: DejaVu Sans, sans-serif;
            font-size: 7.5pt;
        }
        table.detail th {
            border: 0.45pt solid #8fa0b2;
            background: #dfe8f1;
            padding: 1.8mm 1.4mm;
            text-align: left;
            font-weight: bold;
            vertical-align: bottom;
            color: #111;
            font-size: 7.5pt;
            font-family: DejaVu Sans, sans-serif;
        }
        table.detail th.num { text-align: right; }
        table.detail th .th-sub {
            display: block;
            font-size: 6.2pt;
            font-weight: normal;
            color: #556677;
            margin-top: 0.4mm;
            font-family: DejaVu Sans, sans-serif;
        }
        table.detail td {
            border: 0.4pt solid #c5d0dc;
            padding: 1.6mm 1.4mm;
            vertical-align: top;
            font-size: 7.5pt;
        }
        table.detail td.num { text-align: right; white-space: nowrap; }
        table.detail td.moza {
            font-family: DejaVu Serif, serif;
            font-weight: bold;
            background: #f4f7fb;
            font-size: 8pt;
        }
        table.detail td.file {
            font-weight: bold;
            font-size: 7.8pt;
        }
        table.detail td.land {
            font-family: DejaVu Serif, serif;
            font-weight: bold;
            font-size: 7.8pt;
            white-space: nowrap;
        }
        table.detail td.khasra {
            color: #334455;
            font-size: 7.2pt;
        }
        table.detail .main {
            font-family: DejaVu Serif, serif;
            font-weight: bold;
            font-size: 8pt;
        }
        table.detail .breakdown {
            display: block;
            font-family: DejaVu Sans, sans-serif;
            font-size: 6.2pt;
            color: #556677;
            margin-top: 0.5mm;
            font-weight: normal;
            line-height: 1.25;
            white-space: normal;
        }
        table.detail tr.moza-start td { border-top: 0.8pt solid #8fa0b2; }
        table.detail tr.moza-start:first-child td { border-top: 0.4pt solid #c5d0dc; }
        table.detail tr.total td {
            background: #e4ecf4;
            font-weight: bold;
            border-top: 0.9pt solid #5a6a7a;
            border-bottom: 0.9pt solid #5a6a7a;
            font-size: 7.8pt;
        }
        table.detail tr.total td.total-label {
            text-align: right;
            font-family: DejaVu Serif, serif;
            font-size: 8.5pt;
        }

        .empty {
            color: #667788;
            font-size: 9pt;
            font-family: DejaVu Sans, sans-serif;
        }
        .generated {
            margin-top: 3.5mm;
            font-family: DejaVu Sans, sans-serif;
            font-size: 7pt;
            color: #8899aa;
            text-align: right;
        }
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
    $files = $files ?? [];
    $areaBalance = $areaBalance ?? ['formula_columns' => [], 'moza_groups' => [], 'totals' => []];
    $columns = $areaBalance['formula_columns'] ?? [];
    $groups = $areaBalance['moza_groups'] ?? [];
    $detailTotals = $areaBalance['totals'] ?? ['total_land' => '—', 'formula_values' => []];
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
                        <td class="pdf-co-name-cell">
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
                @if($phoneLines->isNotEmpty())
                    @foreach($phoneLines as $phoneLine)
                        <div>{{ $phoneLine }}</div>
                    @endforeach
                @endif
                @if(filled($company?->email))
                    <div class="pdf-co-contact-email">E-mail: {{ $company->email }}</div>
                @endif
            </td>
        </tr>
    </table>
    @if(filled($company?->address))
        <div class="pdf-co-address-bar">{{ $company->address }}</div>
    @endif
</div>

<div class="project-line">Project: <strong>{{ $project->labeledName() }}</strong></div>

<table class="summary">
    <tr>
        <td class="total-cell">
            <div class="total-box">
                <div class="total-label">Total Land</div>
                <div class="total-value">{{ $totalLand }}</div>
            </div>
        </td>
        <td>
            <div class="files-title">Files</div>
            @if($files === [])
                <p class="empty">No sale land files.</p>
            @else
                <table class="file-list">
                    @foreach($files as $fileName)
                        <tr>
                            <td class="num">{{ $loop->iteration }}.</td>
                            <td>{{ $fileName }}</td>
                        </tr>
                    @endforeach
                </table>
            @endif
        </td>
    </tr>
</table>

<div class="section">Details</div>

@if($groups === [])
    <p class="empty">No Mouza land found on the moved sale land files.</p>
@else
    <table class="detail">
        <thead>
            <tr>
                <th>Moza</th>
                <th>File</th>
                <th>Khasra</th>
                <th>Land</th>
                @foreach($columns as $column)
                    <th class="num">
                        {{ $column['column_code'] ?? $column['code'] ?? '' }}
                        @if(!empty($column['short_label']))
                            <span class="th-sub">{{ $column['short_label'] }}</span>
                        @endif
                    </th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach($groups as $group)
                @php
                    $groupFiles = $group['files'] ?? [];
                    if ($groupFiles === []) {
                        $groupFiles = [['file_name' => '—', 'khasra' => '—']];
                    }
                    $rowspan = max(1, (int) ($group['rowspan'] ?? count($groupFiles)));
                @endphp
                @foreach($groupFiles as $fileRow)
                    <tr @if($loop->first) class="moza-start" @endif>
                        @if($loop->first)
                            <td rowspan="{{ $rowspan }}" class="moza">{{ $group['moza'] ?? '—' }}</td>
                        @endif
                        <td class="file">{{ $fileRow['file_name'] ?? '—' }}</td>
                        <td class="khasra">{{ $fileRow['khasra'] ?? '—' }}</td>
                        @if($loop->first)
                            <td rowspan="{{ $rowspan }}" class="land">{{ $group['total_land'] ?? '—' }}</td>
                            @foreach($columns as $column)
                                @php
                                    $formula = ($group['formula_values'] ?? [])[$column['plot_key'] ?? ''] ?? null;
                                @endphp
                                <td rowspan="{{ $rowspan }}" class="num">
                                    @if($formula)
                                        <span class="main">{{ $formula['display'] ?? '—' }}</span>
                                        @if(($formula['breakdown'] ?? '—') !== '—')
                                            <span class="breakdown">{{ $formula['breakdown'] }}</span>
                                        @endif
                                    @else
                                        —
                                    @endif
                                </td>
                            @endforeach
                        @endif
                    </tr>
                @endforeach
            @endforeach
            <tr class="total">
                <td colspan="3" class="total-label">Total</td>
                <td class="land">{{ $detailTotals['total_land'] ?? '—' }}</td>
                @foreach($columns as $column)
                    @php
                        $formula = ($detailTotals['formula_values'] ?? [])[$column['plot_key'] ?? ''] ?? null;
                    @endphp
                    <td class="num">
                        @if($formula)
                            <span class="main">{{ $formula['display'] ?? '—' }}</span>
                            @if(($formula['breakdown'] ?? '—') !== '—')
                                <span class="breakdown">{{ $formula['breakdown'] }}</span>
                            @endif
                        @else
                            —
                        @endif
                    </td>
                @endforeach
            </tr>
        </tbody>
    </table>
@endif

<div class="generated">Generated {{ $generatedAt->format('d M Y, h:i A') }}</div>
</body>
</html>
