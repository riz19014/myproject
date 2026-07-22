<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Sale land — {{ $project->name }}</title>
    <style>
        @include('pdf.partials.page-setup')
        * { box-sizing: border-box; }
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 7.5pt;
            color: #111;
            margin: 0;
            line-height: 1.3;
        }
        .doc-header {
            margin: 0 0 3mm 0;
            padding: 0 0 2mm 0;
            border-bottom: 0.25pt solid #ccc;
        }
        .doc-header h1 {
            font-size: 10pt;
            font-weight: bold;
            margin: 0;
            line-height: 1.2;
        }
        .doc-header .project-name {
            font-size: 8.5pt;
            font-weight: bold;
            margin: 0.5mm 0 0 0;
        }
        .doc-header .generated {
            font-size: 6.5pt;
            color: #666;
            margin: 0.5mm 0 0 0;
        }
        .summary {
            width: 100%;
            border-collapse: collapse;
            font-size: 7pt;
            margin-bottom: 3mm;
        }
        .summary td {
            border: 0.25pt solid #ccc;
            padding: 1.5mm 2mm;
            width: 25%;
            vertical-align: top;
        }
        .summary .lbl {
            display: block;
            font-size: 6pt;
            color: #666;
            margin-bottom: 0.3mm;
        }
        .summary .val {
            font-weight: bold;
            font-size: 7pt;
        }
        .section-title {
            font-size: 8.5pt;
            font-weight: bold;
            margin: 3mm 0 0.5mm 0;
        }
        .section-sub {
            font-size: 6.5pt;
            color: #666;
            margin: 0 0 1.5mm 0;
        }
        table.report {
            width: 100%;
            border-collapse: collapse;
            font-size: 6.5pt;
        }
        table.report th {
            border: 0.25pt solid #bbb;
            background: #f5f5f5;
            padding: 1.2mm 1.2mm;
            text-align: left;
            font-weight: bold;
            vertical-align: bottom;
            font-size: 6pt;
        }
        table.report th.num,
        table.report td.num {
            text-align: right;
            white-space: nowrap;
        }
        table.report th.cen,
        table.report td.cen {
            text-align: center;
        }
        table.report td {
            border: 0.25pt solid #ccc;
            padding: 1mm 1.2mm;
            vertical-align: top;
        }
        table.report--formula {
            font-size: 6pt;
        }
        table.report--formula td {
            padding: 0.8mm 1mm;
        }
        table.report--formula th.formula-head {
            text-align: right;
            vertical-align: bottom;
            line-height: 1.2;
            font-size: 5.5pt;
        }
        table.report--formula th.formula-head .formula-code {
            font-size: 5.5pt;
            font-weight: bold;
        }
        table.report tr.total td {
            background: #f5f5f5;
            font-weight: bold;
            border-top: 0.5pt solid #bbb;
        }
        table.report td.file-name {
            font-weight: bold;
        }
        .empty-note {
            font-size: 7.5pt;
            color: #666;
            margin-bottom: 3mm;
        }
        @include('pdf.partials.company-header-styles')
    </style>
</head>
<body>
    @include('pdf.partials.company-header')
    @php
        $formulaColumns = $saleLandSheet['formula_columns'] ?? [];
        $sheetRows = $saleLandSheet['rows'] ?? [];
        $formulaTotals = $saleLandSheet['formula_totals'] ?? ['total_land' => '—', 'formula_values' => []];
        $fileCount = ($scopedPurchaseFiles ?? collect())->isNotEmpty()
            ? ($scopedPurchaseFiles ?? collect())->count()
            : collect($sheetRows)->where('show_file_name', true)->count();
        $rowCount = count($sheetRows);
    @endphp

    <div class="doc-header">
        <h1>Sale land report</h1>
        <div class="project-name">
            {{ $project->labeledName() }}@if(($scopedPurchaseFiles ?? collect())->isNotEmpty()) — {{ ($scopedPurchaseFiles ?? collect())->pluck('file_name')->implode(', ') }}@endif
        </div>
        <div class="generated">Generated {{ $generatedAt->format('d M Y, H:i') }}</div>
    </div>

    @if($sheetRows === [])
        <p class="empty-note">No sale land records yet for this project.</p>
    @else
        <table class="summary">
            <tr>
                <td>
                    <span class="lbl">Purchase files</span>
                    <span class="val">{{ $fileCount }}</span>
                </td>
                <td>
                    <span class="lbl">Mouza rows</span>
                    <span class="val">{{ $rowCount }}</span>
                </td>
                <td>
                    <span class="lbl">Total land</span>
                    <span class="val">{{ $formulaTotals['total_land'] }}</span>
                </td>
                <td>
                    <span class="lbl">Marla per acre</span>
                    <span class="val">{{ rtrim(rtrim(number_format($marlaPerAcreLand, 4, '.', ''), '0'), '.') }}</span>
                </td>
            </tr>
        </table>

        <div class="section-title">Sale land detail</div>
        <div class="section-sub">One row per Mouza per purchase file.</div>

        <table class="report">
            <tr>
                <th>File name</th>
                <th class="cen">SR</th>
                <th>LP</th>
                <th>Land owner</th>
                <th>Transfer to</th>
                <th>Mouza</th>
                <th>Khasra</th>
                <th class="num">Total land</th>
            </tr>
            @foreach($sheetRows as $row)
                <tr>
                    <td class="file-name">{{ $row['file_name'] }}</td>
                    <td class="cen">{{ $row['sr'] }}</td>
                    <td>{{ $row['land_provider'] }}</td>
                    <td>{{ $row['land_owner'] }}</td>
                    <td>{{ $row['transfer_to'] }}</td>
                    <td>{{ $row['moza'] }}</td>
                    <td>{{ $row['khasra'] }}</td>
                    <td class="num">{{ $row['total_land'] }}</td>
                </tr>
            @endforeach
            <tr class="total">
                <td colspan="7" style="text-align: right;">Total</td>
                <td class="num">{{ $formulaTotals['total_land'] }}</td>
            </tr>
        </table>

        @if($formulaColumns !== [])
            <div class="section-title">Formula file counts</div>
            <div class="section-sub">From project exemption setup. Marla per acre: {{ rtrim(rtrim(number_format($marlaPerAcreLand, 4, '.', ''), '0'), '.') }}.</div>

            <table class="report report--formula">
                <tr>
                    <th class="cen">SR</th>
                    <th>File name</th>
                    <th>Mouza</th>
                    @foreach($formulaColumns as $column)
                        <th class="formula-head">
                            {{ $column['short_label'] }}<br>
                            <span class="formula-code">{{ $column['code'] }}</span>
                        </th>
                    @endforeach
                </tr>
                @foreach($sheetRows as $row)
                    <tr>
                        <td class="cen">{{ $row['sr'] }}</td>
                        <td class="file-name">{{ $row['file_name'] }}</td>
                        <td>{{ $row['moza'] }}</td>
                        @foreach($formulaColumns as $column)
                            @php
                                $formula = $row['formula_values'][$column['plot_key']] ?? null;
                            @endphp
                            <td class="num">{{ $formula ? $formula['display'] : '—' }}</td>
                        @endforeach
                    </tr>
                @endforeach
                <tr class="total">
                    <td colspan="3" style="text-align: right;">Total</td>
                    @foreach($formulaColumns as $column)
                        @php
                            $formula = $formulaTotals['formula_values'][$column['plot_key']] ?? null;
                        @endphp
                        <td class="num">{{ $formula ? $formula['display'] : '—' }}</td>
                    @endforeach
                </tr>
            </table>
        @endif
    @endif

    @if(($scopedPurchaseFiles ?? collect())->isEmpty())
    <div class="section-title">Land sales on this project</div>
    @if($sales->isEmpty())
        <p class="section-sub">No project-level sale land records yet.</p>
    @else
        <table class="report">
            <tr>
                <th class="cen">ID</th>
                <th>Sale land</th>
                <th class="num">Cuttings</th>
                <th class="num">Net saleable</th>
                <th>Parties / buyers</th>
                <th class="num">Total (Rs)</th>
                <th>Date</th>
            </tr>
            @foreach($sales as $sale)
                @php
                    $names = $sale->participants->map(function ($sp) {
                        return $sp->party?->name ?? $sp->customer?->name ?? '—';
                    })->filter()->values();
                    $cutMarla = (float) $sale->landCuttings->sum('land_area_marla');
                    $netMarla = (float) $sale->land_area_marla - $cutMarla;
                @endphp
                <tr>
                    <td class="cen">{{ $sale->id }}</td>
                    <td>{{ \App\Support\LandMeasure::formatAkmsLabelFromMarla((float) $sale->land_area_marla) }}</td>
                    <td class="num">{{ $cutMarla > 0 ? \App\Support\LandMeasure::formatAkmsLabelFromMarla($cutMarla) : '—' }}</td>
                    <td class="num">
                        @if($netMarla < 0)
                            -{{ number_format(abs($netMarla), 2) }} marla
                        @else
                            {{ \App\Support\LandMeasure::formatAkmsLabelFromMarla($netMarla) }}
                        @endif
                    </td>
                    <td>{{ $names->isEmpty() ? '—' : $names->implode(', ') }}</td>
                    <td class="num">{{ number_format((float) $sale->total_amount, 0) }}</td>
                    <td>{{ $sale->created_at?->format('d M Y') }}</td>
                </tr>
            @endforeach
        </table>
    @endif
    @endif
</body>
</html>
