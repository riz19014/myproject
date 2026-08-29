@php
    $collective = $collective ?? [];
    $collectiveId = (int) ($collectiveId ?? ($collective['id'] ?? 0));
    $isOpen = ! empty($collective['is_open']);
    $activeExemption = $activeExemption ?? [
        'summary' => $collective['exemption_summary'] ?? '—',
        'marla_label' => '',
        'plots_line' => '',
        'plot_chips' => [],
    ];
    $chooseModalId = $chooseModalId ?? ('collectiveExemptionModal-'.$collectiveId);
    $viewModalId = $viewModalId ?? ('activeExemptionViewModal-'.$collectiveId);
    $plotChips = $activeExemption['plot_chips'] ?? [];
    if ($plotChips === [] && ! empty($activeExemption['plots_line'])) {
        $plotChips = array_values(array_filter(array_map('trim', explode(',', (string) $activeExemption['plots_line']))));
    }
@endphp

<div class="ssf-toolbar">
    <div class="ssf-toolbar__exemption">
        <div class="ssf-toolbar__exemption-copy">
            <span class="ssf-toolbar__kicker">Active exemption</span>
            <button
                type="button"
                class="ssf-toolbar__exemption-value"
                data-bs-toggle="modal"
                data-bs-target="#{{ $viewModalId }}"
                title="View full exemption"
            >
                {{ $activeExemption['summary'] ?? ($collective['exemption_summary'] ?? '—') }}
            </button>
            <div class="ssf-toolbar__exemption-meta-row">
                @if(!empty($activeExemption['marla_label']))
                    <span class="ssf-toolbar__exemption-meta">{{ $activeExemption['marla_label'] }}</span>
                @endif
                @if($plotChips !== [])
                    <span class="ssf-toolbar__plots" title="Plot file sizes in this exemption">
                        @foreach($plotChips as $chip)
                            <span class="ssf-toolbar__plot-chip">{{ $chip }}</span>
                        @endforeach
                    </span>
                @endif
                @unless($isOpen)
                    <span class="ssf-toolbar__locked">Locked on completed file</span>
                @endunless
            </div>
        </div>
        <div class="ssf-toolbar__btns ssf-toolbar__btns--primary">
            <button type="button" class="btn btn-sm btn-outline-theme" data-bs-toggle="modal" data-bs-target="#{{ $viewModalId }}">
                <i class="bi bi-eye" aria-hidden="true"></i> View
            </button>
            @if($isOpen)
                <button type="button" class="btn btn-sm btn-pink" data-bs-toggle="modal" data-bs-target="#{{ $chooseModalId }}">
                    <i class="bi bi-sliders" aria-hidden="true"></i> Choose
                </button>
                @if($project->isDha())
                    <a
                        href="{{ route('sale.projects.exemption.edit', ['project' => $project, 'return_collective_id' => $collectiveId]) }}"
                        class="btn btn-sm btn-outline-theme"
                    >
                        <i class="bi bi-plus-lg" aria-hidden="true"></i> Add
                    </a>
                @endif
            @endif
        </div>
    </div>

    <div class="ssf-toolbar__groups">
        <div class="ssf-toolbar__group">
            <span class="ssf-toolbar__group-label"><i class="bi bi-printer" aria-hidden="true"></i> Reports</span>
            <div class="ssf-toolbar__btns">
                <a href="{{ route('sale.files.collectives.leftover-land.pdf', [$project, $collectiveId]) }}" class="btn btn-sm btn-outline-theme">
                    Print leftover PDF
                </a>
                <a href="{{ route('sale-land.index') }}" class="btn btn-sm btn-outline-theme">
                    Sold land files
                </a>
            </div>
        </div>

        <div class="ssf-toolbar__group">
            <span class="ssf-toolbar__group-label"><i class="bi bi-flag" aria-hidden="true"></i> Status</span>
            <div class="ssf-toolbar__btns">
                @if($isOpen)
                    <form method="post" action="{{ route('sale.files.collectives.complete', [$project, $collectiveId]) }}"
                          onsubmit="return confirm('Mark {{ $collective['name'] }} complete? It will no longer accept new files or exemption changes.');">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-outline-theme">
                            <i class="bi bi-check2-circle" aria-hidden="true"></i> Mark complete
                        </button>
                    </form>
                @else
                    <form method="post" action="{{ route('sale.files.collectives.reopen', [$project, $collectiveId]) }}">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-outline-secondary">
                            <i class="bi bi-arrow-counterclockwise" aria-hidden="true"></i> Reopen
                        </button>
                    </form>
                @endif
            </div>
        </div>
    </div>
</div>
