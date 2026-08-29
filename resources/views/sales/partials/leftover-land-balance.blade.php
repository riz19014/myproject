@php
    $leftoverColumns = $leftoverColumns ?? [];
    $leftoverFiles = $leftoverFiles ?? [];
    $leftoverTotals = $leftoverTotals ?? [];
    $idPrefix = $idPrefix ?? 'leftover-land';
    $summaryTitle = $summaryTitle ?? 'Summary';
    $summarySubtitle = $summarySubtitle ?? null;
    $summaryBadge = $summaryBadge ?? null;
    $summaryBadgeClass = $summaryBadgeClass ?? '';
    $variant = $variant ?? 'default';
    $detailsId = $idPrefix.'-details';
    $totalPlotChips = [];
    foreach ($leftoverColumns as $column) {
        $plot = ($leftoverTotals['formula_remaining'] ?? [])[$column['plot_key'] ?? ''] ?? null;
        if (! $plot) {
            continue;
        }
        $totalPlotChips[] = [
            'code' => $column['column_code'] ?? '',
            'left' => $plot['remaining_display'] ?? '—',
        ];
    }
@endphp

<div class="leftover-land-balance leftover-land-balance--{{ $variant }}">
    @if($leftoverFiles === [])
        <p class="text-muted small mb-0">No files to show leftover for.</p>
    @else
        <div class="leftover-land-balance__item leftover-land-balance__item--summary">
            <button
                type="button"
                class="leftover-land-balance__summary"
                data-bs-toggle="collapse"
                data-bs-target="#{{ $detailsId }}"
                aria-expanded="false"
                aria-controls="{{ $detailsId }}"
            >
                <span class="leftover-land-balance__chevron" aria-hidden="true"></span>
                <span class="leftover-land-balance__summary-block">
                    <span class="leftover-land-balance__summary-top">
                        <span class="leftover-land-balance__summary-title-wrap">
                            @if($variant === 'sale-file')
                                <span class="leftover-land-balance__eyebrow">Sale file</span>
                            @endif
                            <span class="leftover-land-balance__summary-title-row">
                                <span class="leftover-land-balance__summary-title">{{ $summaryTitle }}</span>
                                @if($summaryBadge)
                                    <span class="leftover-land-balance__status {{ $summaryBadgeClass }}">{{ $summaryBadge }}</span>
                                @endif
                            </span>
                            @if($summarySubtitle)
                                <span class="leftover-land-balance__summary-sub">Total {{ $summarySubtitle }}</span>
                            @endif
                        </span>
                        <span class="leftover-land-balance__hero">
                            <span class="leftover-land-balance__hero-label">Leftover</span>
                            <span class="leftover-land-balance__hero-value">{{ $leftoverTotals['remaining_land'] ?? '—' }}</span>
                        </span>
                    </span>

                    <ul class="leftover-land-balance__summary-list mb-0">
                        <li>
                            <span class="leftover-land-balance__metric-label">Total land</span>
                            <span class="leftover-land-balance__land">{{ $leftoverTotals['total_land'] ?? '—' }}</span>
                        </li>
                        <li>
                            <span class="leftover-land-balance__metric-label">Sold</span>
                            <strong class="leftover-land-balance__metric-sold">{{ $leftoverTotals['sold_land'] ?? '—' }}</strong>
                        </li>
                        <li>
                            <span class="leftover-land-balance__metric-label">Files · Mouzas</span>
                            <strong>{{ (int) ($leftoverTotals['files_count'] ?? 0) }} · {{ (int) ($leftoverTotals['mouzas_count'] ?? 0) }}</strong>
                            @if(($leftoverTotals['mouzas_names'] ?? '—') !== '—')
                                <span class="leftover-land-balance__metric-note">{{ $leftoverTotals['mouzas_names'] }}</span>
                            @endif
                        </li>
                        <li>
                            <span class="leftover-land-balance__metric-label">Khasras</span>
                            <strong>{{ (int) ($leftoverTotals['total_khasras'] ?? 0) }}</strong>
                        </li>
                    </ul>

                    @if($totalPlotChips !== [])
                        <div class="leftover-land-balance__plots leftover-land-balance__plots--summary">
                            @foreach($totalPlotChips as $chip)
                                <span class="leftover-land-balance__plot-chip">
                                    <span class="leftover-land-balance__plot-code">{{ $chip['code'] }}</span>
                                    <span class="leftover-land-balance__plot-left">{{ $chip['left'] }} left</span>
                                </span>
                            @endforeach
                        </div>
                    @endif

                    <span class="leftover-land-balance__hint">
                        <i class="bi bi-chevron-down" aria-hidden="true"></i>
                        Open for exemptions, PDF, and file breakdown
                    </span>
                </span>
            </button>

            <div class="collapse leftover-land-balance__detail" id="{{ $detailsId }}">
                <div class="leftover-land-balance__detail-inner">
                    @if(!empty($beforeStatsView))
                        @include($beforeStatsView, $beforeStatsData ?? [])
                    @endif

                    <div class="leftover-land-balance__stats">
                        <div class="leftover-land-balance__stat is-total">
                            <span class="leftover-land-balance__stat-label">Total land</span>
                            <span class="leftover-land-balance__stat-value">{{ $leftoverTotals['total_land'] ?? '—' }}</span>
                        </div>
                        <div class="leftover-land-balance__stat is-sold">
                            <span class="leftover-land-balance__stat-label">Sold</span>
                            <span class="leftover-land-balance__stat-value">{{ $leftoverTotals['sold_land'] ?? '—' }}</span>
                        </div>
                        <div class="leftover-land-balance__stat is-left">
                            <span class="leftover-land-balance__stat-label">Leftover</span>
                            <span class="leftover-land-balance__stat-value">{{ $leftoverTotals['remaining_land'] ?? '—' }}</span>
                        </div>
                    </div>

                    @if($leftoverColumns !== [])
                        <div class="leftover-land-balance__section">
                            <h3 class="leftover-land-balance__section-title">Plot files left</h3>
                            <div class="table-responsive">
                                <table class="table table-sm align-middle leftover-land-balance__table mb-0">
                                    <thead>
                                        <tr>
                                            <th class="llb-col-plot">Plot</th>
                                            <th class="llb-col-available text-end">Available</th>
                                            <th class="llb-col-sold text-end">Sold</th>
                                            <th class="llb-col-left text-end">Left</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($leftoverColumns as $column)
                                            @php
                                                $plot = ($leftoverTotals['formula_remaining'] ?? [])[$column['plot_key'] ?? ''] ?? null;
                                                $isCommercial = str_starts_with(strtoupper((string) ($column['column_code'] ?? '')), 'C');
                                            @endphp
                                            <tr>
                                                <td class="llb-col-plot">
                                                    <span class="llb-plot-inline">
                                                        <span class="llb-plot-code {{ $isCommercial ? 'is-commercial' : 'is-residential' }}">{{ $column['column_code'] ?? '' }}</span>
                                                        <span class="llb-plot-sub">{{ $column['short_label'] ?? $column['plot_label'] ?? '' }}</span>
                                                    </span>
                                                </td>
                                                <td class="llb-col-available text-end">{{ $plot['available_display'] ?? '—' }}</td>
                                                <td class="llb-col-sold text-end">{{ $plot['sold_display'] ?? '—' }}</td>
                                                <td class="llb-col-left text-end">{{ $plot['remaining_display'] ?? '—' }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endif

                    <div class="leftover-land-balance__section">
                        <h3 class="leftover-land-balance__section-title">Files breakdown</h3>
                        <div class="leftover-land-balance__list">
                            @foreach($leftoverFiles as $file)
                                @php
                                    $detailId = $idPrefix.'-file-'.$file['purchase_file_id'];
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
                                            <span class="leftover-land-balance__meta-sep">·</span>
                                            <span>Mouza {{ $file['moza'] }}</span>
                                            <span class="leftover-land-balance__meta-sep">·</span>
                                            <span>{{ (int) ($file['items_count'] ?? 0) }} {{ (int) ($file['items_count'] ?? 0) === 1 ? 'khasra' : 'khasras' }}</span>
                                            <span class="leftover-land-balance__meta-sep">·</span>
                                            <span class="leftover-land-balance__meta-left">Left {{ $file['remaining_land'] }}</span>
                                        </span>
                                    </button>
                                    <div class="collapse leftover-land-balance__detail" id="{{ $detailId }}">
                                        <div class="leftover-land-balance__detail-inner leftover-land-balance__detail-inner--nested">
                                            <div class="leftover-land-balance__stats leftover-land-balance__stats--nested">
                                                <div class="leftover-land-balance__stat">
                                                    <span class="leftover-land-balance__stat-label">File</span>
                                                    <a href="{{ route('purchase.files.show', $file['purchase_file_id']) }}" class="leftover-land-balance__stat-value text-decoration-none">
                                                        {{ $file['file_name'] }}
                                                    </a>
                                                </div>
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
                                                <div class="leftover-land-balance__stat is-total">
                                                    <span class="leftover-land-balance__stat-label">Total</span>
                                                    <span class="leftover-land-balance__stat-value">{{ $file['total_land'] }}</span>
                                                </div>
                                                <div class="leftover-land-balance__stat is-sold">
                                                    <span class="leftover-land-balance__stat-label">Sold</span>
                                                    <span class="leftover-land-balance__stat-value">{{ $file['sold_land'] }}</span>
                                                </div>
                                                <div class="leftover-land-balance__stat is-left">
                                                    <span class="leftover-land-balance__stat-label">Left</span>
                                                    <span class="leftover-land-balance__stat-value">{{ $file['remaining_land'] }}</span>
                                                </div>
                                            </div>
                                            @if($leftoverColumns !== [])
                                                <div class="table-responsive">
                                                    <table class="table table-sm align-middle leftover-land-balance__table mb-0">
                                                        <thead>
                                                            <tr>
                                                                <th class="llb-col-plot">Plot</th>
                                                                <th class="llb-col-available text-end">Available</th>
                                                                <th class="llb-col-sold text-end">Sold</th>
                                                                <th class="llb-col-left text-end">Left</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            @foreach($leftoverColumns as $column)
                                                                @php
                                                                    $plot = ($file['plots'] ?? [])[$column['plot_key'] ?? ''] ?? null;
                                                                    $isCommercial = str_starts_with(strtoupper((string) ($column['column_code'] ?? '')), 'C');
                                                                @endphp
                                                                <tr>
                                                                    <td class="llb-col-plot">
                                                                        <span class="llb-plot-inline">
                                                                            <span class="llb-plot-code {{ $isCommercial ? 'is-commercial' : 'is-residential' }}">{{ $column['column_code'] ?? '' }}</span>
                                                                            <span class="llb-plot-sub">{{ $column['short_label'] ?? $column['plot_label'] ?? '' }}</span>
                                                                        </span>
                                                                    </td>
                                                                    <td class="llb-col-available text-end">{{ $plot['available_display'] ?? '—' }}</td>
                                                                    <td class="llb-col-sold text-end">{{ $plot['sold_display'] ?? '—' }}</td>
                                                                    <td class="llb-col-left text-end @if($plot && !empty($plot['is_depleted'])) is-depleted @endif">
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
        </div>
    @endif
</div>
