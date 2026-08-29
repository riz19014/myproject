@php
    $collective = $collective ?? [];
    $leftover = $collective['leftover_balance'] ?? ['formula_columns' => [], 'files' => [], 'totals' => []];
    $collectiveId = (int) ($collective['id'] ?? 0);
    $panelId = 'collective-'.$collectiveId;
    $landLabel = $collective['total_land_sheet'] ?? ($collective['total_land_area'] ?? '—');
    $activeExemption = $collective['active_exemption'] ?? [
        'summary' => $collective['exemption_summary'] ?? '—',
        'marla_label' => '1 acre = '.rtrim(rtrim(number_format((float) ($collective['marla_per_acre'] ?? 160), 4, '.', ''), '0'), '.').'M',
        'components' => [],
    ];
    $activeFileCalculator = $collective['active_file_calculator'] ?? [];
    $pickOptions = $exemptionPickOptions ?? ($exemptionOptions ?? []);
    $chooseModalId = 'collectiveExemptionModal-'.$collectiveId;
    $viewModalId = 'activeExemptionViewModal-'.$collectiveId;
    $isOpen = ! empty($collective['is_open']);
@endphp

<div class="saved-sale-panel {{ $isOpen ? 'is-open' : 'is-done' }}" id="{{ $panelId }}">
    @include('sales.partials.leftover-land-balance', [
        'leftoverColumns' => $leftover['formula_columns'] ?? [],
        'leftoverFiles' => $leftover['files'] ?? [],
        'leftoverTotals' => $leftover['totals'] ?? [],
        'idPrefix' => 'collective-'.$collectiveId,
        'summaryTitle' => $collective['name'] ?? 'Sale file',
        'summarySubtitle' => $landLabel,
        'summaryBadge' => $isOpen ? 'Open' : 'Completed',
        'summaryBadgeClass' => $isOpen ? 'is-open' : 'is-done',
        'variant' => 'sale-file',
        'beforeStatsView' => 'sales.partials.collective-summary-toolbar',
        'beforeStatsData' => [
            'project' => $project,
            'collective' => $collective,
            'collectiveId' => $collectiveId,
            'activeExemption' => $activeExemption,
            'chooseModalId' => $chooseModalId,
            'viewModalId' => $viewModalId,
        ],
    ])
</div>

@push('modals')
@if($isOpen)
<div class="modal fade" id="{{ $chooseModalId }}" tabindex="-1" aria-labelledby="{{ $chooseModalId }}-title" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
        <form
            method="post"
            action="{{ route('sale.files.collectives.apply-exemption', [$project, $collectiveId]) }}"
            class="modal-content exemption-modal-content"
        >
            @csrf
            <div class="modal-header exemption-modal-header">
                <div>
                    <h2 class="modal-title h5 mb-1" id="{{ $chooseModalId }}-title">Choose exemption</h2>
                    <p class="small text-muted mb-0">Pick which formula to apply on {{ $collective['name'] ?? 'this sale file' }} ({{ $landLabel }}).</p>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" style="background: #f8fafc;">
                <p class="exemption-pick-hint mb-0">
                    This updates the plot files left numbers using the selected exemption.
                </p>
                <div class="exemption-pick-list mt-3" role="radiogroup" aria-label="Exemption options">
                    @forelse($pickOptions as $index => $option)
                        <label class="exemption-pick-option">
                            <input
                                type="radio"
                                name="snapshot_id"
                                value="{{ $option['id'] ?? '' }}"
                                @checked($index === 0)
                            >
                            <div class="exemption-pick-card">
                                <div class="exemption-pick-card__top">
                                    <h3 class="exemption-pick-card__title">{{ $option['title'] ?? ($option['label'] ?? 'Exemption') }}</h3>
                                    @if(!empty($option['badge']))
                                        <span class="exemption-pick-card__badge {{ !empty($option['is_current']) ? 'is-live' : '' }}">
                                            {{ $option['badge'] }}
                                        </span>
                                    @endif
                                </div>
                                <div class="exemption-pick-card__summary">{{ $option['summary'] ?? ($option['label'] ?? '—') }}</div>
                                <div class="exemption-pick-card__meta">
                                    {{ $option['marla_label'] ?? '' }}
                                    @if(!empty($option['date_label']))
                                        · {{ $option['date_label'] }}
                                    @endif
                                </div>
                                @if(!empty($option['components']))
                                    <div class="exemption-pick-breakdown">
                                        @foreach($option['components'] as $component)
                                            <div class="exemption-pick-comp">
                                                <div class="exemption-pick-comp__head">
                                                    <span class="exemption-pick-comp__title">{{ $component['label'] ?? '' }}</span>
                                                    <span class="exemption-pick-comp__pool">Pool {{ $component['percent'] ?? '' }}</span>
                                                </div>
                                                @if(!empty($component['plots']))
                                                    <ul class="exemption-pick-comp__plots">
                                                        @foreach($component['plots'] as $plot)
                                                            <li>
                                                                <span>{{ $plot['label'] ?? 'Plot' }}</span>
                                                                <span>
                                                                    <strong>{{ $plot['marla_label'] ?? '—' }}</strong>
                                                                    @if(!empty($plot['share_label']) && ($plot['share_percent'] ?? 0) > 0)
                                                                        · share {{ $plot['share_label'] }}
                                                                    @endif
                                                                </span>
                                                            </li>
                                                        @endforeach
                                                    </ul>
                                                @else
                                                    <div class="exemption-pick-comp__empty">No plot marla configured</div>
                                                @endif
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        </label>
                    @empty
                        <div class="exemption-pick-empty">
                            No exemption setups found yet.
                            @if($project->isDha())
                                Use <strong>Add exemption</strong> to create one, then apply it here.
                            @endif
                        </div>
                    @endforelse
                </div>
            </div>
            <div class="modal-footer justify-content-between flex-wrap gap-2">
                <div>
                    @if($project->isDha())
                        <a
                            href="{{ route('sale.projects.exemption.edit', ['project' => $project, 'return_collective_id' => $collectiveId]) }}"
                            class="btn btn-outline-theme"
                        >
                            <i class="bi bi-plus-lg" aria-hidden="true"></i>
                            Add exemption
                        </a>
                    @endif
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <button type="button" class="btn btn-outline-theme" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-pink" @disabled(empty($pickOptions))>Apply exemption</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endif

<div class="modal fade" id="{{ $viewModalId }}" tabindex="-1" aria-labelledby="{{ $viewModalId }}-title" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl modal-dialog-scrollable">
        <div class="modal-content exemption-modal-content">
            <div class="modal-header exemption-modal-header">
                <div>
                    <h2 class="modal-title h5 mb-1" id="{{ $viewModalId }}-title">Active exemption</h2>
                    <p class="small text-muted mb-0">Full formula currently applied on {{ $collective['name'] ?? 'this sale file' }} ({{ $landLabel }}).</p>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" style="padding: 1.2rem 1.35rem; background: #f8fafc;">
                <div class="exemption-view-meta">
                    <span><strong>Summary:</strong> {{ $activeExemption['summary'] ?? '—' }}</span>
                    <span><strong>{{ $activeExemption['marla_label'] ?? '' }}</strong></span>
                    @if(!empty($activeExemption['plots_line']))
                        <span><strong>Plots:</strong> {{ $activeExemption['plots_line'] }}</span>
                    @endif
                </div>

                @forelse(($activeExemption['components'] ?? []) as $component)
                    <div class="exemption-view-component">
                        <div class="exemption-view-component__head">
                            <h3 class="exemption-view-component__title">{{ $component['label'] ?? 'Component' }}</h3>
                            <span class="exemption-view-component__pct">Pool {{ $component['pool_percent_label'] ?? '' }}</span>
                        </div>
                        @if(!empty($component['plot_types']))
                            <div class="table-responsive">
                                <table class="exemption-view-plots">
                                    <thead>
                                        <tr>
                                            <th>Plot file</th>
                                            <th>Share %</th>
                                            <th>Marla / plot</th>
                                            <th>Nominal</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($component['plot_types'] as $plot)
                                            <tr>
                                                <td>{{ $plot['label'] ?? '—' }}</td>
                                                <td>{{ $plot['share_percent_label'] ?? '—' }}</td>
                                                <td>{{ $plot['marla_per_plot_label'] ?? '—' }}</td>
                                                <td>{{ $plot['nominal_marla_label'] ?? '—' }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <p class="small text-muted mb-0">No plot types in this component.</p>
                        @endif
                    </div>
                @empty
                    <div class="exemption-view-empty">
                        No exemption details stored on this sale file yet. Choose or add an exemption first.
                    </div>
                @endforelse

                @if(!empty($activeFileCalculator['rows']))
                    <div class="mt-3">
                        <h3 class="h6 mb-2">Formula on this sale file</h3>
                        @include('sales.partials.exemption-file-calculator-table', ['fileCalculator' => $activeFileCalculator])
                    </div>
                @endif
            </div>
            <div class="modal-footer justify-content-between flex-wrap gap-2">
                <button type="button" class="btn btn-outline-theme" data-bs-dismiss="modal">Close</button>
                @if($isOpen)
                    <button
                        type="button"
                        class="btn btn-pink"
                        data-bs-dismiss="modal"
                        data-bs-toggle="modal"
                        data-bs-target="#{{ $chooseModalId }}"
                    >
                        Choose exemption
                    </button>
                @else
                    <span class="small text-muted">Exemption is locked while this sale file is completed.</span>
                @endif
            </div>
        </div>
    </div>
</div>
@endpush
