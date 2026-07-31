@php
    $leftoverColumns = $leftoverColumns ?? [];
    $leftoverFiles = $leftoverFiles ?? [];
    $leftoverTotals = $leftoverTotals ?? [];
    $totalPlotChips = [];
    foreach ($leftoverColumns as $column) {
        $plot = ($leftoverTotals['formula_remaining'] ?? [])[$column['plot_key'] ?? ''] ?? null;
        if (! $plot) {
            continue;
        }
        $totalPlotChips[] = ($column['column_code'] ?? '').' left '.$plot['remaining_display'];
    }
@endphp

<div class="leftover-land-balance">
    @if($leftoverFiles === [])
        <p class="text-muted small mb-0">No moved sale land files to show leftover for.</p>
    @else
        <div class="leftover-land-balance__item leftover-land-balance__item--summary">
            <button
                type="button"
                class="leftover-land-balance__summary"
                data-bs-toggle="collapse"
                data-bs-target="#leftover-land-details"
                aria-expanded="false"
                aria-controls="leftover-land-details"
            >
                <span class="leftover-land-balance__chevron" aria-hidden="true"></span>
                <span class="leftover-land-balance__summary-block">
                    <span class="leftover-land-balance__summary-title">Summary</span>
                    <ul class="leftover-land-balance__summary-list mb-0">
                        <li>
                            <strong>Total Land:</strong>
                            <span class="leftover-land-balance__land">{{ $leftoverTotals['total_land'] ?? '—' }}</span>
                        </li>
                        <li>
                            <strong>Total Files:</strong>
                            {{ (int) ($leftoverTotals['files_count'] ?? 0) }}
                        </li>
                        <li>
                            <strong>Total Mouzas:</strong>
                            {{ (int) ($leftoverTotals['mouzas_count'] ?? 0) }}
                            @if(($leftoverTotals['mouzas_names'] ?? '—') !== '—')
                                <span class="text-muted">({{ $leftoverTotals['mouzas_names'] }})</span>
                            @endif
                        </li>
                        <li>
                            <strong>Total Khasras:</strong>
                            {{ (int) ($leftoverTotals['total_khasras'] ?? 0) }}
                        </li>
                    </ul>
                    @if($totalPlotChips !== [])
                        <div class="leftover-land-balance__plots leftover-land-balance__plots--summary">
                            @foreach($totalPlotChips as $chip)
                                <span class="leftover-land-balance__plot-chip">{{ $chip }}</span>
                            @endforeach
                        </div>
                    @endif
                </span>
            </button>

            <div class="collapse leftover-land-balance__detail" id="leftover-land-details">
                <div class="leftover-land-balance__detail-inner">
                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <div class="leftover-land-balance__stat is-total">
                                <span class="leftover-land-balance__stat-label">Total land</span>
                                <span class="leftover-land-balance__stat-value">{{ $leftoverTotals['total_land'] ?? '—' }}</span>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="leftover-land-balance__stat is-sold">
                                <span class="leftover-land-balance__stat-label">Sold</span>
                                <span class="leftover-land-balance__stat-value">{{ $leftoverTotals['sold_land'] ?? '—' }}</span>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="leftover-land-balance__stat is-left">
                                <span class="leftover-land-balance__stat-label">Leftover</span>
                                <span class="leftover-land-balance__stat-value">{{ $leftoverTotals['remaining_land'] ?? '—' }}</span>
                            </div>
                        </div>
                    </div>

                    @if($leftoverColumns !== [])
                        <h3 class="h6 mb-2">Plot files left</h3>
                        <div class="table-responsive mb-4">
                            <table class="table table-sm table-striped table-theme mb-0 align-middle">
                                <thead>
                                    <tr>
                                        <th>Plot</th>
                                        <th class="text-end">Available</th>
                                        <th class="text-end">Sold</th>
                                        <th class="text-end">Left</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($leftoverColumns as $column)
                                        @php
                                            $plot = ($leftoverTotals['formula_remaining'] ?? [])[$column['plot_key'] ?? ''] ?? null;
                                        @endphp
                                        <tr>
                                            <td>
                                                <span class="fw-semibold">{{ $column['column_code'] ?? '' }}</span>
                                                <span class="text-muted small d-block">{{ $column['short_label'] ?? $column['plot_label'] ?? '' }}</span>
                                            </td>
                                            <td class="text-end small">{{ $plot['available_display'] ?? '—' }}</td>
                                            <td class="text-end small">{{ $plot['sold_display'] ?? '—' }}</td>
                                            <td class="text-end small fw-semibold">{{ $plot['remaining_display'] ?? '—' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif

                    <h3 class="h6 mb-2">Files breakdown</h3>
                    <div class="leftover-land-balance__list">
                        @foreach($leftoverFiles as $file)
                            @php
                                $detailId = 'leftover-file-'.$file['purchase_file_id'];
                            @endphp
                            <div class="leftover-land-balance__item leftover-land-balance__item--nested">
                                <button
                                    type="button"
                                    class="leftover-land-balance__summary leftover-land-balance__summary--nested"
                                    data-bs-toggle="collapse"
                                    data-bs-target="#{{ $detailId }}"
                                    aria-expanded="false"
                                    aria-controls="{{ $detailId }}"
                                >
                                    <span class="leftover-land-balance__chevron" aria-hidden="true"></span>
                                    <span class="leftover-land-balance__meta">
                                        <strong>{{ $file['file_name'] }}</strong>
                                        <span class="text-muted">·</span>
                                        Mouza {{ $file['moza'] }}
                                        <span class="text-muted">·</span>
                                        {{ (int) ($file['items_count'] ?? 0) }} {{ (int) ($file['items_count'] ?? 0) === 1 ? 'khasra' : 'khasras' }}
                                        <span class="text-muted">·</span>
                                        Left {{ $file['remaining_land'] }}
                                    </span>
                                </button>
                                <div class="collapse leftover-land-balance__detail" id="{{ $detailId }}">
                                    <div class="leftover-land-balance__detail-inner">
                                        <div class="row g-3 mb-3">
                                            <div class="col-md-3">
                                                <div class="leftover-land-balance__stat">
                                                    <span class="leftover-land-balance__stat-label">File</span>
                                                    <a href="{{ route('purchase.files.show', $file['purchase_file_id']) }}" class="leftover-land-balance__stat-value text-decoration-none">
                                                        {{ $file['file_name'] }}
                                                    </a>
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="leftover-land-balance__stat">
                                                    <span class="leftover-land-balance__stat-label">Status</span>
                                                    <span class="leftover-land-balance__stat-value">
                                                        @if(($file['status'] ?? '') === 'Fully Sold')
                                                            <span class="badge text-bg-secondary">Fully Sold</span>
                                                        @elseif(($file['status'] ?? '') === 'Partially Sold')
                                                            <span class="badge text-bg-warning">Partially Sold</span>
                                                        @else
                                                            <span class="badge text-bg-success">{{ $file['status'] ?? 'Available' }}</span>
                                                        @endif
                                                    </span>
                                                </div>
                                            </div>
                                            <div class="col-md-2">
                                                <div class="leftover-land-balance__stat is-total">
                                                    <span class="leftover-land-balance__stat-label">Total</span>
                                                    <span class="leftover-land-balance__stat-value">{{ $file['total_land'] }}</span>
                                                </div>
                                            </div>
                                            <div class="col-md-2">
                                                <div class="leftover-land-balance__stat is-sold">
                                                    <span class="leftover-land-balance__stat-label">Sold</span>
                                                    <span class="leftover-land-balance__stat-value">{{ $file['sold_land'] }}</span>
                                                </div>
                                            </div>
                                            <div class="col-md-2">
                                                <div class="leftover-land-balance__stat is-left">
                                                    <span class="leftover-land-balance__stat-label">Left</span>
                                                    <span class="leftover-land-balance__stat-value">{{ $file['remaining_land'] }}</span>
                                                </div>
                                            </div>
                                        </div>
                                        @if($leftoverColumns !== [])
                                            <div class="table-responsive">
                                                <table class="table table-sm table-striped table-theme mb-0 align-middle">
                                                    <thead>
                                                        <tr>
                                                            <th>Plot</th>
                                                            <th class="text-end">Available</th>
                                                            <th class="text-end">Sold</th>
                                                            <th class="text-end">Left</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @foreach($leftoverColumns as $column)
                                                            @php
                                                                $plot = ($file['plots'] ?? [])[$column['plot_key'] ?? ''] ?? null;
                                                            @endphp
                                                            <tr>
                                                                <td>
                                                                    <span class="fw-semibold">{{ $column['column_code'] ?? '' }}</span>
                                                                    <span class="text-muted small d-block">{{ $column['short_label'] ?? $column['plot_label'] ?? '' }}</span>
                                                                </td>
                                                                <td class="text-end small">{{ $plot['available_display'] ?? '—' }}</td>
                                                                <td class="text-end small">{{ $plot['sold_display'] ?? '—' }}</td>
                                                                <td class="text-end small fw-semibold @if($plot && !empty($plot['is_depleted'])) text-muted @endif">
                                                                    {{ $plot['remaining_display'] ?? '—' }}
                                                                </td>
                                                            </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
