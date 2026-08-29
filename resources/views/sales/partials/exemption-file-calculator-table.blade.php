@php
    use App\Support\LandMeasure;
    use App\Support\SaleExemptionFileCalculator;
    $fileCalculator = $fileCalculator ?? [];
    $rows = $fileCalculator['rows'] ?? [];
    $totalMarla = (float) ($fileCalculator['total_marla'] ?? 0);
    $acres = (float) ($fileCalculator['acres'] ?? 0);
@endphp

<style>
    .exc-calc-table {
        --exc-border: rgba(15, 23, 42, 0.12);
        border-collapse: separate !important;
        border-spacing: 0;
        overflow: hidden;
        border-radius: 12px;
        border: 1px solid var(--exc-border);
        background: #fff;
        margin-bottom: 0;
    }
    .exc-calc-table > :not(caption) > * > * {
        border-bottom-width: 0;
        box-shadow: none !important;
    }
    .exc-calc-table.table-striped > tbody > tr:nth-of-type(odd) > * {
        --bs-table-bg-type: transparent;
        --bs-table-accent-bg: transparent;
    }
    .exc-calc-table thead th {
        font-size: 0.68rem;
        font-weight: 800;
        letter-spacing: 0.04em;
        text-transform: uppercase;
        white-space: nowrap;
        border-bottom: 2px solid rgba(15, 23, 42, 0.18) !important;
        vertical-align: middle;
        padding: 0.55rem 0.45rem;
    }
    .exc-calc-table tbody td {
        border-top: 1px solid rgba(15, 23, 42, 0.1) !important;
        vertical-align: middle;
        padding: 0.45rem 0.45rem;
    }
    .exc-calc-table tbody tr:first-child td {
        border-top: 0 !important;
    }

    /* Strongly distinct column colors (header darker, body lighter of same hue) */
    .exc-calc-table thead th.exc-col-code { background: #334155 !important; color: #fff !important; }
    .exc-calc-table tbody td.exc-col-code { background: #cbd5e1 !important; color: #0f172a !important; }

    .exc-calc-table thead th.exc-col-plot { background: #0369a1 !important; color: #fff !important; }
    .exc-calc-table tbody td.exc-col-plot { background: #bae6fd !important; color: #0c4a6e !important; }

    .exc-calc-table thead th.exc-col-share { background: #ca8a04 !important; color: #fff !important; }
    .exc-calc-table tbody td.exc-col-share { background: #fef08a !important; color: #713f12 !important; }

    .exc-calc-table thead th.exc-col-calc { background: #4f46e5 !important; color: #fff !important; }
    .exc-calc-table tbody td.exc-col-calc { background: #c7d2fe !important; color: #312e81 !important; }

    .exc-calc-table thead th.exc-col-files { background: #2563eb !important; color: #fff !important; }
    .exc-calc-table tbody td.exc-col-files { background: #93c5fd !important; color: #1e3a8a !important; }

    .exc-calc-table thead th.exc-col-full { background: #15803d !important; color: #fff !important; }
    .exc-calc-table tbody td.exc-col-full { background: #86efac !important; color: #14532d !important; }

    .exc-calc-table thead th.exc-col-after-dec { background: #0f766e !important; color: #fff !important; }
    .exc-calc-table tbody td.exc-col-after-dec { background: #99f6e4 !important; color: #134e4a !important; }

    .exc-calc-table thead th.exc-col-dec-marla { background: #c2410c !important; color: #fff !important; }
    .exc-calc-table tbody td.exc-col-dec-marla { background: #fdba74 !important; color: #7c2d12 !important; }

    .exc-calc-table thead th.exc-col-frac-marla { background: #be123c !important; color: #fff !important; }
    .exc-calc-table tbody td.exc-col-frac-marla { background: #fda4af !important; color: #881337 !important; }

    .exc-calc-table thead th.exc-col-sqft { background: #7e22ce !important; color: #fff !important; }
    .exc-calc-table tbody td.exc-col-sqft { background: #d8b4fe !important; color: #581c87 !important; }

    .exc-calc-table thead th.exc-col-pool { background: #475569 !important; color: #fff !important; }
    .exc-calc-table tbody td.exc-col-pool { background: #e2e8f0 !important; color: #334155 !important; }

    .exc-calc-table tbody tr:hover td {
        filter: brightness(0.94);
    }
</style>

<p class="small mb-2">
    Acres: <strong>{{ SaleExemptionFileCalculator::formatFileCount($acres) }}</strong>
    · Total: <strong>{{ LandMeasure::formatAkmsLabelFromMarla($totalMarla) }}</strong>
    · <span class="text-muted">1 marla = {{ (int) LandMeasure::SQFT_PER_MARLA }} sqft</span>
</p>
<div class="table-responsive">
    <table class="table table-sm mb-0 exc-calc-table">
        <thead>
            <tr>
                <th class="exc-col-code">Code</th>
                <th class="exc-col-plot">Plot file</th>
                <th class="exc-col-share">Share %</th>
                <th class="exc-col-calc">Calculation</th>
                <th class="exc-col-files text-end">Files</th>
                <th class="exc-col-full text-end">Full</th>
                <th class="exc-col-after-dec text-end">After decimal</th>
                <th class="exc-col-dec-marla text-end">Decimal in marla</th>
                <th class="exc-col-frac-marla text-end">After . marla</th>
                <th class="exc-col-sqft text-end">SQFT</th>
                <th class="exc-col-pool text-end">Pool line marla</th>
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $row)
                @php
                    $fractionMarla = (float) ($row['fraction_marla'] ?? 0);
                    $afterDecimalMarla = SaleExemptionFileCalculator::fractionalMarlaPart($fractionMarla);
                    $remainderSqft = SaleExemptionFileCalculator::remainderSqftFromMarla($fractionMarla);
                @endphp
                <tr>
                    <td class="exc-col-code fw-semibold">{{ $row['code'] }}</td>
                    <td class="exc-col-plot">{{ $row['plot_label'] }}</td>
                    <td class="exc-col-share">{{ SaleExemptionFileCalculator::formatFileCount($row['share_percent']) }}%</td>
                    <td class="exc-col-calc small font-monospace">
                        {{ SaleExemptionFileCalculator::formatMarlaWithUnit($row['marla_per_plot']) }}
                        × {{ SaleExemptionFileCalculator::formatFileCount($acres) }}
                        = {{ SaleExemptionFileCalculator::formatMarlaWithUnit($row['product_marla']) }}
                        :{{ SaleExemptionFileCalculator::formatMarlaWithUnit($row['nominal_marla']) }}
                    </td>
                    <td class="exc-col-files text-end fw-semibold">{{ SaleExemptionFileCalculator::formatFileCount($row['file_count']) }}</td>
                    <td class="exc-col-full text-end">{{ $row['full_files'] }}</td>
                    <td class="exc-col-after-dec text-end small">{{ $row['fraction_files'] > 0 ? SaleExemptionFileCalculator::formatFileCount($row['fraction_files']) : '—' }}</td>
                    <td class="exc-col-dec-marla text-end small">{{ $fractionMarla > 0 ? SaleExemptionFileCalculator::formatMarlaWithUnit($fractionMarla) : '—' }}</td>
                    <td class="exc-col-frac-marla text-end small">{{ $afterDecimalMarla > 0 ? SaleExemptionFileCalculator::formatMarlaWithUnit($afterDecimalMarla) : '—' }}</td>
                    <td class="exc-col-sqft text-end small">{{ SaleExemptionFileCalculator::formatSqft($remainderSqft) }}</td>
                    <td class="exc-col-pool text-end small">{{ SaleExemptionFileCalculator::formatMarlaWithUnit($row['product_marla']) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="11" class="text-muted small">No plot types configured. Set up project exemption rules first.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
