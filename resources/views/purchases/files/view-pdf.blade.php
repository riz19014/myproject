<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $purchaseFile->file_name }} — Purchase file</title>
    <style>
        @page { margin: 8mm 6mm 10mm 6mm; }
        * { box-sizing: border-box; }
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 8pt;
            color: #111;
            margin: 0;
            line-height: 1.25;
        }
        h1 {
            font-size: 11pt;
            font-weight: bold;
            margin: 0 0 1mm 0;
        }
        .meta {
            font-size: 7pt;
            color: #333;
            margin: 0 0 3mm 0;
        }
        table.sheet {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }
        table.sheet th,
        table.sheet td {
            border: 0.5pt solid #333;
            padding: 1.5mm 1mm;
            vertical-align: middle;
        }
        table.sheet th {
            background: #eee;
            font-weight: bold;
            text-align: center;
            font-size: 7.5pt;
        }
        table.sheet td {
            text-align: right;
            font-size: 8pt;
        }
        table.sheet tfoot td {
            background: #f0f0f0;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <h1>{{ $purchaseFile->file_name }}</h1>
    <div class="meta">
        <strong>Project:</strong> {{ $purchaseFile->project?->name ?? '—' }}
        · <strong>Date:</strong> {{ $purchaseFile->file_date?->format('d M Y') ?? '—' }}
        · <strong>Generated:</strong> {{ $generatedAt->format('d M Y, g:i A') }}
    </div>

    @include('purchases.files.partials.show-sheet-table', [
        'sheetGrid' => $sheetGrid,
        'tableClass' => 'sheet',
        'wrapResponsive' => false,
    ])
</body>
</html>
