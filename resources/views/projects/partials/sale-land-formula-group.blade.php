<div class="sale-land-formula-group card card-theme mb-4">
    <div class="card-header sale-land-formula-group__head py-3">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
            <div>
                <span class="badge rounded-pill bg-secondary bg-opacity-25 text-dark border me-2">{{ $group['code'] }}</span>
                <span class="fw-semibold fs-6">{{ $group['plot_label'] }}</span>
                <span class="text-muted small ms-2">({{ $group['component_label'] }})</span>
            </div>
            <span class="text-muted small">{{ count($group['moza_groups']) }} Mouza{{ count($group['moza_groups']) === 1 ? '' : 's' }}</span>
        </div>
    </div>
    <div class="card-body p-0">
        @foreach($group['moza_groups'] as $mozaGroup)
            <div class="sale-land-moza-block {{ $loop->last ? '' : 'border-bottom' }}">
                <div class="sale-land-moza-block__head px-3 py-2">
                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
                        <div class="fw-semibold small">
                            Mouza: <span class="text-dark">{{ $mozaGroup['moza'] }}</span>
                            <span class="text-muted fw-normal">· {{ $mozaGroup['land_purchase_name'] }}</span>
                        </div>
                        <div class="small text-muted">
                            Mouza area: <strong class="sale-land-formula-group__highlight">{{ $mozaGroup['moza_land_area'] }}</strong>
                            · Files: <strong class="sale-land-formula-group__highlight">{{ $mozaGroup['formula_files'] }}</strong>
                            @if(($mozaGroup['formula_files_breakdown'] ?? '—') !== '—')
                                <span class="text-muted">({{ $mozaGroup['formula_files_breakdown'] }})</span>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-sm table-striped table-theme mb-0 align-middle sale-land-moza-block__table">
                        <thead>
                            <tr>
                                <th style="width: 52px;">SR No.</th>
                                <th>Land provider</th>
                                <th>Transfer from</th>
                                <th>Transfer to</th>
                                <th>Mouza</th>
                                <th>Khasra no.</th>
                                <th>Land area</th>
                                <th class="text-end" style="min-width: 120px;">Formula files</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($mozaGroup['rows'] as $row)
                                <tr>
                                    <td class="text-muted">{{ $row['sr'] }}</td>
                                    <td class="fw-semibold small">{{ $row['land_provider'] }}</td>
                                    <td class="small">{{ $row['transfer_from'] }}</td>
                                    <td class="small">{{ $row['transfer_to'] }}</td>
                                    <td class="small">{{ $row['moza'] }}</td>
                                    <td class="small">{{ $row['khasra'] }}</td>
                                    <td class="small sale-land-formula-group__highlight">{{ $row['land_area'] }}</td>
                                    <td class="text-end small">
                                        <span class="fw-semibold">{{ $row['formula_files'] }}</span>
                                        @if(($row['formula_files_breakdown'] ?? '—') !== '—')
                                            <div class="text-muted" style="font-size: 0.75rem;">{{ $row['formula_files_breakdown'] }}</div>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endforeach
    </div>
</div>
