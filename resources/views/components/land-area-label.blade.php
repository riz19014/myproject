{{--
  Reusable area label bound to canonical marla for live unit switching.

  Usage:
    <x-land-area-label :marla="$totalMarla" />
    <x-land-area-label :marla="$out" empty="—" class="society-land-akms-value" />
--}}
@props([
    'marla' => null,
    'unit' => null,
    'empty' => '—',
    'signed' => true,
])

@php
    $value = $marla === null || $marla === '' ? null : (float) $marla;
    $displayUnit = \App\Support\LandMeasure::normalizeDisplayUnit(
        $unit ?? config('land_measure.default_display_unit', 'acre')
    );
    if ($value === null || abs($value) < 1e-12) {
        $label = $empty;
        $attr = '';
    } else {
        $label = \App\Support\LandMeasure::formatLabelFromMarla($value, $displayUnit);
        $attr = rtrim(rtrim(sprintf('%.12F', $value), '0'), '.');
    }
@endphp

<span
    {{ $attributes }}
    @if($attr !== '')
        data-land-marla="{{ $attr }}"
    @else
        data-land-marla=""
    @endif
    data-land-empty="{{ $empty }}"
>{{ $label }}</span>
