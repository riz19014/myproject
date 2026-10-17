@php
    $collective = $collective ?? [];
    $collectiveId = (int) ($collectiveId ?? ($collective['id'] ?? 0));
    $isOpen = ! empty($collective['is_open']);
    $activeExemption = $activeExemption ?? [
        'summary' => $collective['exemption_summary'] ?? '—',
        'marla_label' => '',
    ];
    $chooseModalId = $chooseModalId ?? ('collectiveExemptionModal-'.$collectiveId);
    $viewModalId = $viewModalId ?? ('activeExemptionViewModal-'.$collectiveId);
    $exemptionSummary = $activeExemption['summary'] ?? ($collective['exemption_summary'] ?? '—');
@endphp

<div class="ssf-toolbar ssf-toolbar--simple">
    <div class="ssf-simple-bar">
        <button
            type="button"
            class="ssf-simple-bar__value"
            data-bs-toggle="modal"
            data-bs-target="#{{ $viewModalId }}"
            title="View exemption"
        >
            {{ \Illuminate\Support\Str::limit($exemptionSummary, 42) }}
        </button>
        <div class="ssf-simple-bar__actions">
            @if($isOpen)
                <button type="button" class="btn btn-sm btn-pink" data-bs-toggle="modal" data-bs-target="#{{ $chooseModalId }}">Change</button>
            @else
                <span class="ssf-simple-bar__locked">Locked</span>
            @endif
            <a href="{{ route('sale.files.collectives.leftover-land.pdf', [$project, $collectiveId]) }}" class="btn btn-sm btn-outline-theme">PDF</a>
            @if($isOpen)
                <form method="post" action="{{ route('sale.files.collectives.complete', [$project, $collectiveId]) }}" class="d-inline">
                    @csrf
                    <button
                        type="button"
                        class="btn btn-sm btn-outline-theme btn-delete-confirm"
                        data-title="Mark complete?"
                        data-text="Mark {{ $collective['name'] ?? 'this sale file' }} complete?"
                        data-confirm="Yes, mark complete"
                    >Done</button>
                </form>
            @else
                <form method="post" action="{{ route('sale.files.collectives.reopen', [$project, $collectiveId]) }}" class="d-inline">
                    @csrf
                    <button
                        type="button"
                        class="btn btn-sm btn-outline-secondary btn-delete-confirm"
                        data-title="Reopen sale file?"
                        data-text="Reopen {{ $collective['name'] ?? 'this sale file' }}?"
                        data-confirm="Yes, reopen"
                    >Reopen</button>
                </form>
            @endif
        </div>
    </div>
</div>
