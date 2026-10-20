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
    $pickOptions = $collective['exemption_pick_options'] ?? ($exemptionPickOptions ?? ($exemptionOptions ?? []));
    $chooseModalId = 'collectiveExemptionModal-'.$collectiveId;
    $viewModalId = 'activeExemptionViewModal-'.$collectiveId;
    $isOpen = ! empty($collective['is_open']);
    $appliedPickIndex = collect($pickOptions)->search(fn ($option) => ! empty($option['is_applied']));
    if ($appliedPickIndex === false) {
        $appliedPickIndex = 0;
    }
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
        'summaryMouza' => $collective['mouzas_names'] ?? null,
        'variant' => 'sale-file',
        'canExcludeToSaleLand' => $isOpen,
        'excludeProject' => $project,
        'excludeCollectiveId' => $collectiveId,
        'excludeCollectiveName' => $collective['name'] ?? 'this sale file',
        'beforeStatsView' => 'sales.partials.collective-summary-toolbar',
        'beforeStatsData' => [
            'project' => $project,
            'collective' => $collective,
            'collectiveId' => $collectiveId,
            'activeExemption' => $activeExemption,
            'chooseModalId' => $chooseModalId,
            'viewModalId' => $viewModalId,
        ],
        'canSelectFilesForMerge' => $isOpen,
    ])
</div>

@push('modals')
@if($isOpen)
<div class="modal fade" id="{{ $chooseModalId }}" tabindex="-1" aria-labelledby="{{ $chooseModalId }}-title" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-md">
        <form
            method="post"
            action="{{ route('sale.files.collectives.apply-exemption', [$project, $collectiveId]) }}"
            class="modal-content exemption-modal-content"
        >
            @csrf
            <div class="modal-header exemption-modal-header py-2 px-3">
                <h2 class="modal-title h6 mb-0" id="{{ $chooseModalId }}-title">Choose exemption</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body py-2 px-3">
                <div class="exemption-pick-list" role="radiogroup" aria-label="Exemption options">
                    @forelse($pickOptions as $index => $option)
                        @php
                            $lineTitle = $option['title'] ?? ($option['label'] ?? 'Exemption');
                            $lineBadge = $option['badge'] ?? null;
                            $isApplied = ! empty($option['is_applied']);
                            $isLive = ! empty($option['is_current']);
                            $components = $option['components'] ?? [];
                            $metaParts = array_filter([
                                $option['marla_label'] ?? null,
                                $option['date_label'] ?? null,
                            ]);
                        @endphp
                        <label class="exemption-pick-line {{ $isApplied ? 'is-applied' : '' }}">
                            <input
                                type="radio"
                                name="snapshot_id"
                                value="{{ $option['id'] ?? '' }}"
                                @checked($index === $appliedPickIndex)
                            >
                            <span class="exemption-pick-line__radio" aria-hidden="true"></span>
                            <span class="exemption-pick-line__body">
                                <span class="exemption-pick-line__text">
                                    <span class="exemption-pick-line__title">{{ $lineTitle }}</span>
                                    @if($isApplied)
                                        <span class="exemption-pick-line__badge is-applied">Applied</span>
                                    @elseif($lineBadge)
                                        <span class="exemption-pick-line__badge {{ $isLive ? 'is-live' : '' }}">{{ $lineBadge }}</span>
                                    @endif
                                </span>
                                @if($metaParts !== [])
                                    <span class="exemption-pick-line__meta">{{ implode(' · ', $metaParts) }}</span>
                                @endif
                                @if($components !== [])
                                    <span class="exemption-pick-line__comps">
                                        @foreach($components as $component)
                                            @php
                                                $compSlug = strtolower((string) ($component['slug'] ?? ''));
                                                $compClass = str_contains($compSlug, 'commerc') ? 'is-commercial' : (str_contains($compSlug, 'resid') ? 'is-residential' : '');
                                            @endphp
                                            <span class="exemption-pick-line__comp {{ $compClass }}">
                                                <span class="exemption-pick-line__comp-head">
                                                    <span class="exemption-pick-line__comp-title">{{ $component['label'] ?? 'Component' }}</span>
                                                    <span class="exemption-pick-line__comp-pool">Pool {{ $component['percent'] ?? '' }}</span>
                                                </span>
                                                @forelse(($component['plots'] ?? []) as $plot)
                                                    <span class="exemption-pick-line__plot">
                                                        <span class="exemption-pick-line__plot-name">{{ $plot['label'] ?? 'Plot' }}</span>
                                                        <span class="exemption-pick-line__plot-vals">
                                                            {{ $plot['marla_label'] ?? '—' }}
                                                            @if(!empty($plot['share_label']) && ($plot['share_percent'] ?? 0) > 0)
                                                                · share {{ $plot['share_label'] }}
                                                            @endif
                                                        </span>
                                                    </span>
                                                @empty
                                                    <span class="exemption-pick-line__plot-empty">No plot marla configured</span>
                                                @endforelse
                                            </span>
                                        @endforeach
                                    </span>
                                @endif
                            </span>
                        </label>
                    @empty
                        <div class="exemption-pick-empty">
                            No exemption setups yet.
                            @if($project->isDha())
                                Use <strong>Add</strong> to create one.
                            @endif
                        </div>
                    @endforelse
                </div>
            </div>
            <div class="modal-footer py-2 px-3 justify-content-between flex-wrap gap-2">
                <div>
                    @if($project->isDha())
                        <a
                            href="{{ route('sale.projects.exemption.edit', ['project' => $project, 'return_collective_id' => $collectiveId]) }}"
                            class="btn btn-sm btn-outline-theme"
                        >
                            Add
                        </a>
                    @endif
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <button type="button" class="btn btn-sm btn-outline-theme" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-sm btn-pink" @disabled(empty($pickOptions))>Apply</button>
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
                    <h2 class="modal-title h5 mb-1" id="{{ $viewModalId }}-title">Applied exemption</h2>
                    <p class="small text-muted mb-0">This formula is active on {{ $collective['name'] ?? 'this sale file' }} and drives Plot files left ({{ $landLabel }}).</p>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" style="padding: 1.2rem 1.35rem; background: #f8fafc;">
                <div class="exemption-view-applied-banner">
                    <span class="exemption-view-applied-banner__badge">Applied</span>
                    <span>Matches Available / Sold / Left in Plot files left</span>
                </div>
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
