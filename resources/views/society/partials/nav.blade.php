@if(session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
@endif
@php
    $links = [
        ['route' => 'society.dashboard', 'label' => 'Dashboard', 'icon' => 'bi-speedometer2'],
        ['route' => 'society.acquisition', 'label' => 'Land acquisition', 'icon' => 'bi-bag-check'],
        ['route' => 'society.master-plan', 'label' => 'Master plan', 'icon' => 'bi-grid-3x3-gap'],
        ['route' => 'society.blocks', 'label' => 'Blocks & plots', 'icon' => 'bi-house'],
        ['route' => 'society.development', 'label' => 'Development', 'icon' => 'bi-cone-striped'],
        ['route' => 'society.budgets', 'label' => 'Budgets', 'icon' => 'bi-wallet2'],
        ['route' => 'society.sales', 'label' => 'Sales', 'icon' => 'bi-receipt'],
        ['route' => 'society.reports', 'label' => 'Reports', 'icon' => 'bi-graph-up'],
        ['route' => 'society.approvals', 'label' => 'Approvals', 'icon' => 'bi-check2-square'],
        ['route' => 'projects.partners', 'label' => 'Partners', 'icon' => 'bi-people'],
    ];
@endphp
<div class="d-flex flex-wrap gap-2 mb-4">
    <a href="{{ route('projects.show', $project) }}" class="btn btn-sm btn-outline-theme">
        <i class="bi bi-arrow-left me-1"></i>Hub
    </a>
    @foreach($links as $link)
        <a href="{{ route($link['route'], $project) }}"
           class="btn btn-sm {{ request()->routeIs($link['route'].'*') ? 'btn-pink' : 'btn-outline-theme' }}">
            <i class="bi {{ $link['icon'] }} me-1" aria-hidden="true"></i>{{ $link['label'] }}
        </a>
    @endforeach
</div>
