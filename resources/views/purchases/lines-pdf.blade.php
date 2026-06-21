<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Purchase Land — {{ config('app.name') }}</title>
    <style>
        @include('pdf.partials.page-setup')
        * { box-sizing: border-box; }
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 8.5px;
            color: #0f172a;
            margin: 0;
            line-height: 1.35;
        }
        .doc-header {
            border-bottom: 3px solid #1e3a5f;
            padding-bottom: 10px;
            margin-bottom: 14px;
        }
        .doc-title {
            font-size: 17px;
            font-weight: bold;
            color: #1e3a5f;
            margin: 0 0 4px 0;
            letter-spacing: 0.02em;
        }
        .meta {
            font-size: 8px;
            color: #64748b;
        }
        .meta strong { color: #475569; }

        table.data {
            width: 100%;
            border-collapse: collapse;
            margin-top: 4px;
        }
        table.data th,
        table.data td {
            border: 1px solid #cbd5e1;
            padding: 5px 6px;
            vertical-align: top;
        }
        table.data thead th {
            background: #1e3a5f;
            color: #fff;
            font-size: 7.5px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            font-weight: bold;
        }
        table.data tbody tr:nth-child(even) td {
            background: #f8fafc;
        }
        .num { text-align: right; white-space: nowrap; }
        .cen { text-align: center; }
        .fwb { font-weight: bold; }
        .small { font-size: 7.5px; color: #475569; }

        tfoot td {
            background: #e2e8f0 !important;
            border-top: 2px solid #1e3a5f !important;
            font-weight: bold;
        }
        .tfoot-lbl {
            font-size: 7px;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: #64748b;
            font-weight: bold;
            margin-bottom: 3px;
        }
        .empty-msg {
            padding: 20px;
            text-align: center;
            color: #64748b;
            border: 1px dashed #cbd5e1;
            border-radius: 6px;
            margin-top: 8px;
        }
        @include('pdf.partials.company-header-styles')
    </style>
</head>
<body>
    @include('pdf.partials.company-header')
    <header class="doc-header">
        <h1 class="doc-title">Purchase Land</h1>
        <p class="meta">
            <strong>{{ config('app.name') }}</strong>
            · Generated <strong>{{ $generatedAt->format('Y-m-d H:i') }}</strong>
            · {{ $purchaseLineCount }} {{ $purchaseLineCount === 1 ? 'line' : 'lines' }}
        </p>
    </header>

    @if($purchaseItems->isEmpty())
        <p class="empty-msg">No purchase land records to print.</p>
    @else
        <table class="data">
            <thead>
                <tr>
                    <th style="width: 4%;">ID</th>
                    <th style="width: 11%;">File</th>
                    <th style="width: 11%;">Project</th>
                    <th style="width: 12%;">Party</th>
                    <th style="width: 8%;">Moza</th>
                    <th style="width: 8%;">Khasra</th>
                    <th style="width: 18%;">Area</th>
                    <th style="width: 8%;" class="num">Rs / acre</th>
                    <th style="width: 10%;" class="num">Line total (Rs)</th>
                    <th style="width: 8%;" class="cen">Date</th>
                </tr>
            </thead>
            <tbody>
                @foreach($purchaseItems as $item)
                    <tr>
                        <td class="cen">{{ $item->id }}</td>
                        <td class="small">{{ e($item->purchaseFile?->file_name ?? '—') }}</td>
                        <td>{{ e($item->project?->name ?? '—') }}</td>
                        <td>{{ e($item->party?->name ?? '—') }}</td>
                        <td>{{ e($item->moza ?? '') ?: '—' }}</td>
                        <td>{{ e($item->khasra ?? '') ?: '—' }}</td>
                        <td class="small">{{ \App\Support\LandMeasure::formatAkmsLabelFromMarla((float) $item->land_area_marla) }}</td>
                        <td class="num">{{ number_format((float) $item->amount_per_acre, 0) }}</td>
                        <td class="num fwb">{{ number_format((float) $item->line_total_rs, 0) }}</td>
                        <td class="cen small">{{ $item->created_at?->format('Y-m-d') }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="6" class="num" style="vertical-align: middle;">
                        <div class="tfoot-lbl">Totals</div>
                    </td>
                    <td style="vertical-align: middle;">
                        <div class="tfoot-lbl">Total area</div>
                        <div style="font-size: 9px;">{{ \App\Support\LandMeasure::formatAkmsLabelFromMarla($purchaseTotalMarla) }}</div>
                        <div class="small" style="margin-top: 3px; font-weight: normal;">{{ \App\Support\LandMeasure::formatMarlaTotal($purchaseTotalMarla) }}</div>
                    </td>
                    <td class="num" style="vertical-align: middle; color: #64748b;">—</td>
                    <td class="num" style="vertical-align: middle;">
                        <div class="tfoot-lbl">Line total (Rs)</div>
                        <div style="font-size: 11px;">Rs {{ number_format($purchaseTotalRs, 0) }}</div>
                    </td>
                    <td></td>
                </tr>
            </tfoot>
        </table>
    @endif
</body>
</html>
