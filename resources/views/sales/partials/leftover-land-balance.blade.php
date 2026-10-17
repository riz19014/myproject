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
    $canExcludeToSaleLand = ! empty($canExcludeToSaleLand);
    $excludeProject = $excludeProject ?? null;
    $excludeCollectiveId = (int) ($excludeCollectiveId ?? 0);
    $excludeCollectiveName = $excludeCollectiveName ?? 'this sale file';
    $isSaleFile = $variant === 'sale-file';
    $filesCount = (int) ($leftoverTotals['files_count'] ?? count($leftoverFiles));
@endphp

<div class="leftover-land-balance leftover-land-balance--{{ $variant }}">
    @if($leftoverFiles === [])
        <p class="text-muted small mb-0">No files to show leftover for.</p>
    @else
        <div class="leftover-land-balance__item leftover-land-balance__item--summary">
            <button
                type="button"
                class="leftover-land-balance__summary {{ $isSaleFile ? 'leftover-land-balance__summary--simple' : '' }}"
                data-bs-toggle="collapse"
                data-bs-target="#{{ $detailsId }}"
                aria-expanded="false"
                aria-controls="{{ $detailsId }}"
            >
                @if($isSaleFile)
                    <span class="ssf-simple-row">
                        <span class="ssf-simple-row__main">
                            <span class="leftover-land-balance__summary-title">{{ $summaryTitle }}</span>
                            @if($summaryBadge)
                                <span class="leftover-land-balance__status {{ $summaryBadgeClass }}">{{ $summaryBadge }}</span>
                            @endif
                            <span class="ssf-simple-row__meta">{{ $filesCount }} {{ $filesCount === 1 ? 'file' : 'files' }}</span>
                        </span>
                        <span class="ssf-simple-row__left">
                            <span class="ssf-simple-row__left-value">{{ $leftoverTotals['remaining_land'] ?? '—' }}</span>
                            <span class="ssf-simple-row__left-label">left</span>
                        </span>
                        <span class="ssf-simple-row__chevron" aria-hidden="true"></span>
                    </span>
                @else
                    <span class="leftover-land-balance__chevron" aria-hidden="true"></span>
                    <span class="leftover-land-balance__summary-block">
                        <span class="leftover-land-balance__summary-top">
                            <span class="leftover-land-balance__summary-title-wrap">
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
                            </li>
                            <li>
                                <span class="leftover-land-balance__metric-label">Khasras</span>
                                <strong>{{ (int) ($leftoverTotals['total_khasras'] ?? 0) }}</strong>
                            </li>
                        </ul>
                        <span class="leftover-land-balance__hint">
                            <i class="bi bi-chevron-down" aria-hidden="true"></i>
                            Open details
                        </span>
                    </span>
                @endif
            </button>

            <div class="collapse leftover-land-balance__detail" id="{{ $detailsId }}">
                <div class="leftover-land-balance__detail-inner {{ $isSaleFile ? 'leftover-land-balance__detail-inner--simple' : '' }}">
                    @if(!empty($beforeStatsView))
                        @include($beforeStatsView, $beforeStatsData ?? [])
                    @endif

                    @unless($isSaleFile)
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
                    @endunless

                    @if($leftoverColumns !== [])
                        <div class="leftover-land-balance__section">
                            <h3 class="leftover-land-balance__section-title">{{ $isSaleFile ? 'Plots left' : 'Plot files left' }}</h3>
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
                        <h3 class="leftover-land-balance__section-title">{{ $isSaleFile ? 'Files' : 'Files breakdown' }}</h3>
                        <div class="leftover-land-balance__list">
                            @foreach($leftoverFiles as $file)
                                @php
                                    $detailId = $idPrefix.'-file-'.$file['purchase_file_id'];
                                @endphp
                                <div class="leftover-land-balance__item leftover-land-balance__item--nested">
                                    <div class="leftover-land-balance__file-row">
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
                                                @if($isSaleFile)
                                                    <span class="leftover-land-balance__meta-sep">·</span>
                                                    <span class="leftover-land-balance__meta-left">Left {{ $file['remaining_land'] }}</span>
                                                @else
                                                    <span class="leftover-land-balance__meta-sep">·</span>
                                                    <span>Mouza {{ $file['moza'] }}</span>
                                                    <span class="leftover-land-balance__meta-sep">·</span>
                                                    <span>{{ (int) ($file['items_count'] ?? 0) }} {{ (int) ($file['items_count'] ?? 0) === 1 ? 'khasra' : 'khasras' }}</span>
                                                    <span class="leftover-land-balance__meta-sep">·</span>
                                                    <span class="leftover-land-balance__meta-left">Left {{ $file['remaining_land'] }}</span>
                                                @endif
                                            </span>
                                        </button>
                                        @if($canExcludeToSaleLand && $excludeProject && $excludeCollectiveId > 0)
                                            <form
                                                method="post"
                                                action="{{ route('sale.files.collectives.exclude', [$excludeProject, $excludeCollectiveId, $file['purchase_file_id']]) }}"
                                                class="leftover-land-balance__exclude-form"
                                            >
                                                @csrf
                                                <button
                                                    type="button"
                                                    class="btn btn-sm btn-outline-danger leftover-land-balance__exclude-btn btn-delete-confirm"
                                                    data-title="Exclude to Sale Land?"
                                                    data-text="Remove &quot;{{ $file['file_name'] }}&quot; from {{ $excludeCollectiveName }} and return it to Sale Land?"
                                                    data-confirm="Yes, exclude"
                                                    title="Exclude to Sale Land"
                                                    aria-label="Exclude {{ $file['file_name'] }} to Sale Land"
                                                >
                                                    Exclude
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                    <div class="collapse leftover-land-balance__detail" id="{{ $detailId }}">
                                        <div class="leftover-land-balance__detail-inner leftover-land-balance__detail-inner--nested">
                                            <div class="ssf-file-mini">
                                                <a href="{{ route('purchase.files.show', $file['purchase_file_id']) }}" class="ssf-file-mini__link">
                                                    Open purchase file
                                                </a>
                                                <span class="ssf-file-mini__sep">·</span>
                                                <span>
                                                    @if(($file['status'] ?? '') === 'Fully Sold')
                                                        Fully sold
                                                    @elseif(($file['status'] ?? '') === 'Partially Sold')
                                                        Partially sold
                                                    @else
                                                        {{ $file['status'] ?? 'Available' }}
                                                    @endif
                                                </span>
                                                <span class="ssf-file-mini__sep">·</span>
                                                <span>Total {{ $file['total_land'] }}</span>
                                                <span class="ssf-file-mini__sep">·</span>
                                                <span>Sold {{ $file['sold_land'] }}</span>
                                                <span class="ssf-file-mini__sep">·</span>
                                                <span class="text-success fw-semibold">Left {{ $file['remaining_land'] }}</span>
                                            </div>
                                            @if($leftoverColumns !== [])
                                                <div class="table-responsive mt-2">
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
