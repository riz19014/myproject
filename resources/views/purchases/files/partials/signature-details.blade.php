@php
    $blocks = $signatureDetails['blocks'] ?? [];
    $columnsPerRow = 3;
@endphp

@if(count($blocks) > 0)
<style>
    .signature-details {
        margin-top: 2.5mm;
        page-break-inside: avoid;
    }
    .signature-details__title {
        margin: 0 0 1.2mm;
        padding-bottom: 0.6mm;
        border-bottom: 0.6pt solid #222;
        font-size: 8pt;
        font-weight: bold;
        text-transform: uppercase;
        letter-spacing: 0.3pt;
    }
    .signature-details__grid {
        width: 100%;
        border-collapse: collapse;
        table-layout: fixed;
    }
    .signature-details__cell {
        width: 33.333%;
        padding: 1.2mm 2.2mm 1.8mm 0;
        border: 0;
        vertical-align: top;
    }
    .signature-details__cell:nth-child(3n) {
        padding-right: 0;
    }
    .signature-details__role {
        margin: 0 0 0.8mm;
        font-size: 7pt;
        font-weight: bold;
        text-transform: uppercase;
        color: #222;
        letter-spacing: 0.2pt;
    }
    .signature-details__field {
        margin: 0 0 1.1mm;
        font-size: 6.5pt;
        line-height: 1.15;
        color: #333;
    }
    .signature-details__label {
        display: inline-block;
        min-width: 14mm;
        font-weight: bold;
        color: #444;
    }
    .signature-details__value {
        color: #111;
    }
    .signature-details__sign {
        margin-top: 3.5mm;
        font-size: 6.5pt;
        color: #333;
    }
    .signature-details__sign-label {
        margin: 0 0 1.8mm;
        font-weight: bold;
        color: #444;
    }
    .signature-details__line {
        width: 92%;
        border-top: 0.5pt solid #333;
        height: 0;
    }
</style>

<div class="signature-details">
    <div class="signature-details__title">Parties &amp; Signatures</div>
    <table class="signature-details__grid">
        @foreach(array_chunk($blocks, $columnsPerRow) as $rowBlocks)
            <tr>
                @foreach($rowBlocks as $block)
                    <td class="signature-details__cell">
                        <div class="signature-details__role">{{ $block['role'] }}</div>
                        <div class="signature-details__field">
                            <span class="signature-details__label">Name:</span>
                            <span class="signature-details__value">{{ $block['name'] }}</span>
                        </div>
                        <div class="signature-details__field">
                            <span class="signature-details__label">CNIC:</span>
                            <span class="signature-details__value">{{ $block['cnic'] }}</span>
                        </div>
                        <div class="signature-details__sign">
                            <div class="signature-details__sign-label">Signature:</div>
                            <div class="signature-details__line"></div>
                        </div>
                    </td>
                @endforeach
                @for($i = count($rowBlocks); $i < $columnsPerRow; $i++)
                    <td class="signature-details__cell"></td>
                @endfor
            </tr>
        @endforeach
    </table>
</div>
@endif
