<style>
    .signature-details {
        margin-top: 4mm;
        page-break-inside: avoid;
    }
    .signature-details__title {
        margin: 0 0 1.5mm;
        padding-bottom: 0.8mm;
        border-bottom: 0.7pt solid #222;
        font-size: 8.5pt;
        font-weight: bold;
        text-transform: uppercase;
        letter-spacing: 0.4pt;
    }
    .signature-details__table {
        width: 100%;
        border-collapse: collapse;
        table-layout: fixed;
    }
    .signature-details__cell {
        width: 33.333%;
        padding: 1.5mm 2mm;
        border: 0.5pt solid #555;
        vertical-align: top;
    }
    .signature-details__role {
        margin-bottom: 1mm;
        font-size: 7pt;
        font-weight: bold;
        text-transform: uppercase;
        color: #333;
    }
    .signature-details__person {
        margin-bottom: 0.8mm;
        font-size: 7.5pt;
        line-height: 1.25;
    }
    .signature-details__name {
        font-weight: bold;
        color: #111;
    }
    .signature-details__cnic {
        color: #444;
    }
    .signature-details__empty {
        font-size: 7.5pt;
        color: #666;
        font-style: italic;
    }
    .signature-details__line {
        margin-top: 6mm;
        padding-top: 0.8mm;
        border-top: 0.5pt solid #333;
        font-size: 7pt;
        color: #444;
        text-align: center;
    }
</style>

<div class="signature-details">
    <div class="signature-details__title">Parties &amp; Signatures</div>
    <table class="signature-details__table">
        <tr>
            <td class="signature-details__cell">
                <div class="signature-details__role">Seller(s)</div>
                @forelse($signatureDetails['sellers'] as $seller)
                    <div class="signature-details__person">
                        <span class="signature-details__name">{{ $seller['name'] }}</span>
                        <span class="signature-details__cnic">· CNIC: {{ $seller['cnic'] }}</span>
                    </div>
                @empty
                    <div class="signature-details__empty">No seller recorded</div>
                @endforelse
                <div class="signature-details__line">Seller signature(s)</div>
            </td>
            <td class="signature-details__cell">
                <div class="signature-details__role">Buyer(s)</div>
                @forelse($signatureDetails['buyers'] as $buyer)
                    <div class="signature-details__person">
                        <span class="signature-details__name">{{ $buyer['name'] }}</span>
                        <span class="signature-details__cnic">· CNIC: {{ $buyer['cnic'] }}</span>
                    </div>
                @empty
                    <div class="signature-details__empty">No buyer recorded</div>
                @endforelse
                <div class="signature-details__line">Buyer signature(s)</div>
            </td>
            <td class="signature-details__cell">
                <div class="signature-details__role">Accountant</div>
                <div class="signature-details__person">
                    <span class="signature-details__name">{{ $signatureDetails['accountant'] }}</span>
                </div>
                <div class="signature-details__line">Accountant signature</div>
            </td>
        </tr>
    </table>
</div>
