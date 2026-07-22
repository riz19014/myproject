@props([
    'project' => null,
    'name' => null,
    'isDha' => null,
])

@php
    $displayName = $name ?? ($project?->name ?? '—');
    $dha = $isDha !== null
        ? (bool) $isDha
        : (bool) ($project?->isDha() ?? false);
@endphp

<span {{ $attributes->class(['project-name-with-dot']) }}>
    <span @class(['project-dha-dot', 'is-dha' => $dha, 'is-not-dha' => ! $dha]) title="{{ $dha ? 'DHA project' : 'Non-DHA project' }}" aria-hidden="true"></span>
    <span class="project-name-with-dot__text">{{ $displayName }}</span>
</span>
