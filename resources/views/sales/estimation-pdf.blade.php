<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Sale estimation — {{ $projectFile->file_number }}</title>
    <style>
        @page { margin: 6mm 8mm 10mm 8mm; size: A4 landscape; }
        * { box-sizing: border-box; }
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 8pt;
            color: #111;
            margin: 0;
            line-height: 1.3;
        }
        @include('pdf.partials.company-header-styles')
        .doc-title {
            text-align: center;
            font-size: 11pt;
            font-weight: bold;
            margin: 0 0 2mm 0;
            letter-spacing: 0.02em;
        }
        .doc-subtitle {
            text-align: center;
            font-size: 8pt;
            color: #444;
            margin: 0 0 3mm 0;
        }
        .summary-table {
            width: 100%;
            border-collapse: collapse;
            margin: 0 0 2.5mm 0;
            font-size: 7.5pt;
        }
        .summary-table td {
            border: 0.5pt solid #bbb;
            padding: 1.8mm 2.5mm;
            vertical-align: top;
            width: 25%;
        }
        .summary-table__label {
            display: block;
            font-size: 6.5pt;
            font-weight: bold;
            color: #666;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            margin-bottom: 0.5mm;
        }
        .summary-table__value {
            display: block;
            font-size: 8.5pt;
            font-weight: bold;
            color: #111;
        }
        .summary-table__sub {
            display: block;
            font-size: 6.5pt;
            color: #555;
            margin-top: 0.3mm;
        }
        .summary-table td.accent {
            background: #fff8f0;
            border-color: #e8a86a;
        }
        .meta-row {
            width: 100%;
            border-collapse: collapse;
            margin: 0 0 2.5mm 0;
            font-size: 7pt;
        }
        .meta-row td {
            padding: 0 2mm 0 0;
            border: 0;
            vertical-align: top;
        }
        .meta-row__label {
            font-weight: bold;
            color: #555;
        }
        .rates-row {
            width: 100%;
            border-collapse: collapse;
            margin: 0 0 2.5mm 0;
            font-size: 7pt;
        }
        .rates-row td {
            border: 0.5pt solid #ccc;
            padding: 1.2mm 2mm;
            background: #fafafa;
        }
        .rates-row__code {
            font-weight: bold;
            color: #1d4ed8;
        }
        table.est {
            width: 100%;
            border-collapse: collapse;
            font-size: 7pt;
            margin-top: 1mm;
        }
        table.est th {
            border: 0.5pt solid #000;
            background: #e8e8e8;
            padding: 1.2mm 1.5mm;
            text-align: left;
            font-weight: bold;
            vertical-align: middle;
        }
        table.est th .est-th-line {
            display: block;
            line-height: 1.1;
        }
        table.est th.num,
        table.est td.num {
            text-align: right;
            white-space: nowrap;
        }
        table.est td {
            border: 0.5pt solid #333;
            padding: 1.3mm 1.5mm;
            vertical-align: top;
        }
        table.est td.calc {
            font-size: 6.5pt;
            font-family: DejaVu Sans Mono, monospace;
        }
        table.est tr.footer td {
            background: #f0f0f0;
            font-weight: bold;
            border-top: 0.75pt solid #000;
        }
        table.est tr.footer-accent td {
            background: #fff8f0;
        }
        .rs-words {
            display: block;
            font-size: 6pt;
            font-weight: normal;
            color: #555;
        }
    </style>
</head>
<body>
@php
    use App\Support\LandMeasure;
    use App\Support\SaleExemptionFileCalculator;
    $acres = (float) ($fileCalculator['acres'] ?? 0);
    $rows = $fileCalculator['rows'] ?? [];
@endphp


<div class="doc-title">Sale Estimation</div>
<div class="doc-subtitle">
    {{ $project->name }} · File {{ $projectFile->file_number }}
    · Generated {{ $generatedAt->format('d M Y, h:i A') }}
</div>

<table class="summary-table">
    <tr>
        <td>
            <span class="summary-table__label">Land area</span>
            <span class="summary-table__value">{{ LandMeasure::formatAkmsLabelFromMarla($fileMarla) }}</span>
        </td>
        <td>
            <span class="summary-table__label">Acres</span>
            <span class="summary-table__value">{{ SaleExemptionFileCalculator::formatFileCount($acres) }}</span>
            <span class="summary-table__sub">1 acre = {{ rtrim(rtrim(number_format($marlaPerAcreLand, 4, '.', ''), '0'), '.') }} marla</span>
        </td>
        <td class="accent">
            <span class="summary-table__label">Land value</span>
            <span class="summary-table__value">
                @if($landValueEstimate !== null)
                    Rs {{ SaleExemptionFileCalculator::formatRs($landValueEstimate) }}
                @else
                    —
                @endif
            </span>
            @if($landValueEstimate !== null && SaleExemptionFileCalculator::formatRsWords($landValueEstimate) !== '')
                <span class="summary-table__sub">{{ SaleExemptionFileCalculator::formatRsWords($landValueEstimate) }}</span>
            @endif
            @if($saleAmountPerAcre)
                <span class="summary-table__sub">@ {{ SaleExemptionFileCalculator::formatRsWithWords((float) $saleAmountPerAcre) }}/acre</span>
            @endif
        </td>
        <td class="accent">
            <span class="summary-table__label">Plot files total</span>
            <span class="summary-table__value">
                @if(($fileCalculator['total_sale_amount'] ?? null) !== null)
                    Rs {{ SaleExemptionFileCalculator::formatRs($fileCalculator['total_sale_amount']) }}
                @else
                    —
                @endif
            </span>
            @if(($fileCalculator['total_sale_amount'] ?? null) !== null && SaleExemptionFileCalculator::formatRsWords($fileCalculator['total_sale_amount']) !== '')
                <span class="summary-table__sub">{{ SaleExemptionFileCalculator::formatRsWords($fileCalculator['total_sale_amount']) }}</span>
            @endif
        </td>
    </tr>
</table>

<table class="meta-row">
    <tr>
        @foreach($config->components() as $component)
            @php
                $slug = $component->slug;
                $poolMarla = $poolsByComponent[$slug] ?? 0;
            @endphp
            <td>
                <span class="meta-row__label">{{ $component->label }} pool</span>
                {{ rtrim(rtrim(number_format($config->poolPercent($slug), 4, '.', ''), '0'), '.') }}%
                · {{ LandMeasure::formatAkmsLabelFromMarla($poolMarla) }}
            </td>
        @endforeach
    </tr>
</table>

@if($rows !== [])
    <table class="rates-row">
        <tr>
            @foreach($rows as $row)
                <td>
                    <span class="rates-row__code">{{ $row['sale_code'] ?? $row['code'] }}</span>
                    {{ $row['plot_label'] }}
                    @if($row['amount_per_file'] !== null)
                        · Rs {{ SaleExemptionFileCalculator::formatRs($row['amount_per_file']) }}/file
                    @endif
                </td>
            @endforeach
        </tr>
    </table>
@endif

<table class="est">
    <thead>
        <tr>
            <th style="width:5%;"><span class="est-th-line">Code</span></th>
            <th style="width:10%;"><span class="est-th-line">Plot file</span></th>
            <th style="width:5%;" class="num"><span class="est-th-line">Share</span><span class="est-th-line">%</span></th>
            <th style="width:16%;"><span class="est-th-line">Calculation</span></th>
            <th style="width:5%;" class="num"><span class="est-th-line">Files</span></th>
            <th style="width:4%;" class="num"><span class="est-th-line">Full</span></th>
            <th style="width:6%;" class="num"><span class="est-th-line">After</span><span class="est-th-line">decimal</span></th>
            <th style="width:7%;" class="num"><span class="est-th-line">Decimal</span><span class="est-th-line">in marla</span></th>
            <th style="width:7%;" class="num"><span class="est-th-line">Pool line</span><span class="est-th-line">marla</span></th>
            <th style="width:9%;" class="num"><span class="est-th-line">Rs /</span><span class="est-th-line">file</span></th>
            <th style="width:10%;" class="num"><span class="est-th-line">Line</span><span class="est-th-line">total Rs</span></th>
        </tr>
    </thead>
    <tbody>
        @forelse($rows as $row)
            <tr>
                <td><strong>{{ $row['sale_code'] ?? $row['code'] }}</strong></td>
                <td>{{ $row['plot_label'] }}</td>
                <td class="num">{{ SaleExemptionFileCalculator::formatFileCount($row['share_percent']) }}%</td>
                <td class="calc">
                    {{ SaleExemptionFileCalculator::formatMarlaWithUnit($row['marla_per_plot']) }}
                    × {{ SaleExemptionFileCalculator::formatFileCount($acres) }}
                    = {{ SaleExemptionFileCalculator::formatMarlaWithUnit($row['product_marla']) }}
                    :{{ SaleExemptionFileCalculator::formatMarlaWithUnit($row['nominal_marla']) }}
                </td>
                <td class="num"><strong>{{ SaleExemptionFileCalculator::formatFileCount($row['file_count']) }}</strong></td>
                <td class="num">{{ $row['full_files'] }}</td>
                <td class="num">{{ $row['fraction_files'] > 0 ? SaleExemptionFileCalculator::formatFileCount($row['fraction_files']) : '—' }}</td>
                <td class="num">{{ $row['fraction_marla'] > 0 ? SaleExemptionFileCalculator::formatMarlaWithUnit($row['fraction_marla']) : '—' }}</td>
                <td class="num">{{ SaleExemptionFileCalculator::formatMarlaWithUnit($row['product_marla']) }}</td>
                <td class="num">
                    {{ SaleExemptionFileCalculator::formatRs($row['amount_per_file'] ?? null) }}
                    @if(($row['amount_per_file'] ?? null) !== null && SaleExemptionFileCalculator::formatRsWords($row['amount_per_file']) !== '')
                        <span class="rs-words">{{ SaleExemptionFileCalculator::formatRsWords($row['amount_per_file']) }}</span>
                    @endif
                </td>
                <td class="num">
                    <strong>{{ SaleExemptionFileCalculator::formatRs($row['line_sale_amount'] ?? null) }}</strong>
                    @if(($row['line_sale_amount'] ?? null) !== null && SaleExemptionFileCalculator::formatRsWords($row['line_sale_amount']) !== '')
                        <span class="rs-words">{{ SaleExemptionFileCalculator::formatRsWords($row['line_sale_amount']) }}</span>
                    @endif
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="11">No plot types configured.</td>
            </tr>
        @endforelse
    </tbody>
    @if(($fileCalculator['total_sale_amount'] ?? null) !== null || $landValueEstimate !== null)
        <tfoot>
            @if(($fileCalculator['total_sale_amount'] ?? null) !== null)
                <tr class="footer">
                    <td colspan="10" class="num">Plot files total</td>
                    <td class="num">
                        {{ SaleExemptionFileCalculator::formatRs($fileCalculator['total_sale_amount']) }}
                        @if(SaleExemptionFileCalculator::formatRsWords($fileCalculator['total_sale_amount']) !== '')
                            <span class="rs-words">{{ SaleExemptionFileCalculator::formatRsWords($fileCalculator['total_sale_amount']) }}</span>
                        @endif
                    </td>
                </tr>
            @endif
            @if($landValueEstimate !== null)
                <tr class="footer footer-accent">
                    <td colspan="10" class="num">Land value (acres × Rs/acre)</td>
                    <td class="num">
                        {{ SaleExemptionFileCalculator::formatRs($landValueEstimate) }}
                        @if(SaleExemptionFileCalculator::formatRsWords($landValueEstimate) !== '')
                            <span class="rs-words">{{ SaleExemptionFileCalculator::formatRsWords($landValueEstimate) }}</span>
                        @endif
                    </td>
                </tr>
            @endif
        </tfoot>
    @endif
</table>
</body>
</html>
