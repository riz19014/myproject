@php
    /** @var array<string, mixed> $summary */
@endphp
<table class="ledger-file-summary">
    <tr>
        <td class="ledger-file-summary__left" valign="top">
            @if(filled($summary['headline'] ?? null))
                <div class="ledger-file-summary__headline">{{ $summary['headline'] }}</div>
            @endif
            <div class="ledger-file-summary__line">
                Project: {{ $summary['project_name'] ?? '—' }} · Date: {{ $summary['file_date'] ?? '—' }}
            </div>
        </td>
        <td class="ledger-file-summary__right" valign="top" align="right">
            <div class="ledger-file-summary__land-label">Land Total</div>
            <div class="ledger-file-summary__land-rs">Rs {{ number_format((float) ($summary['land_total_rs'] ?? 0), 2) }}</div>
            @if(($summary['land_area_label'] ?? '—') !== '—')
                <div class="ledger-file-summary__land-area">{{ $summary['land_area_label'] }}</div>
            @endif
        </td>
    </tr>
    @if(filled($summary['dealers'] ?? null) || filled($summary['owner_names'] ?? null))
        <tr>
            <td colspan="2" class="ledger-file-summary__meta-row" valign="top">
                <table class="ledger-file-summary__meta-table">
                    <tr>
                        @if(filled($summary['dealers'] ?? null))
                            <td class="ledger-file-summary__meta-cell" valign="top">
                                <span class="ledger-file-summary__meta-label">Dealers:</span>
                                {{ $summary['dealers'] }}
                            </td>
                        @endif
                        @if(filled($summary['owner_names'] ?? null))
                            <td class="ledger-file-summary__meta-cell" valign="top">
                                <span class="ledger-file-summary__meta-label">Land owners:</span>
                                {{ $summary['owner_names'] }}
                            </td>
                        @endif
                    </tr>
                </table>
            </td>
        </tr>
    @endif
</table>
