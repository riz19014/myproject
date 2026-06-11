@php
    use App\Support\LandMeasure;
    use App\Support\SaleExemptionFileCalculator;
    $fileCalculator = $fileCalculator ?? [];
    $rows = $fileCalculator['rows'] ?? [];
    $totalMarla = (float) ($fileCalculator['total_marla'] ?? 0);
    $acres = (float) ($fileCalculator['acres'] ?? 0);
@endphp

<p class="small mb-2">
    Acres: <strong>{{ SaleExemptionFileCalculator::formatFileCount($acres) }}</strong>
    · Total: <strong>{{ LandMeasure::formatAkmsLabelFromMarla($totalMarla) }}</strong>
</p>
<div class="table-responsive">
    <table class="table table-sm table-striped table-theme mb-0">
        <thead>
            <tr>
                <th>Code</th>
                <th>Plot file</th>
                <th>Share %</th>
                <th>Calculation</th>
                <th class="text-end">Files</th>
                <th class="text-end">Full</th>
                <th class="text-end">After decimal</th>
                <th class="text-end">Decimal in marla</th>
                <th class="text-end">Pool line marla</th>
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $row)
                <tr>
                    <td class="fw-semibold">{{ $row['code'] }}</td>
                    <td>{{ $row['plot_label'] }}</td>
                    <td>{{ SaleExemptionFileCalculator::formatFileCount($row['share_percent']) }}%</td>
                    <td class="small font-monospace">
                        {{ SaleExemptionFileCalculator::formatMarlaWithUnit($row['marla_per_plot']) }}
                        × {{ SaleExemptionFileCalculator::formatFileCount($acres) }}
                        = {{ SaleExemptionFileCalculator::formatMarlaWithUnit($row['product_marla']) }}
                        :{{ SaleExemptionFileCalculator::formatMarlaWithUnit($row['nominal_marla']) }}
                    </td>
                    <td class="text-end fw-semibold">{{ SaleExemptionFileCalculator::formatFileCount($row['file_count']) }}</td>
                    <td class="text-end">{{ $row['full_files'] }}</td>
                    <td class="text-end small">{{ $row['fraction_files'] > 0 ? SaleExemptionFileCalculator::formatFileCount($row['fraction_files']) : '—' }}</td>
                    <td class="text-end small">{{ $row['fraction_marla'] > 0 ? SaleExemptionFileCalculator::formatMarlaWithUnit($row['fraction_marla']) : '—' }}</td>
                    <td class="text-end small text-muted">{{ SaleExemptionFileCalculator::formatMarlaWithUnit($row['product_marla']) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" class="text-muted small">No plot types configured. Set up project exemption rules first.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
