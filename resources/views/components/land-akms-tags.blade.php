{{--
  Reusable whole-line land totals as small tags.
  Shows TOTAL acre / kanal / marla / sqft for the full amount
  (not residual A—K—M—SQFT parts).

  Usage:
    <x-land-akms-tags :total-marla="$totalMarla" />
    <x-land-akms-tags :acre="7" :kanal="0" :marla="19" :sqft="88" />
--}}
@props([
    'totalMarla' => null,
    'acre' => null,
    'kanal' => null,
    'marla' => null,
    'sqft' => null,
    'showZero' => true,
    'decimals' => 2,
])

@php
    if ($totalMarla !== null) {
        $sourceMarla = (float) $totalMarla;
    } else {
        $sourceMarla = \App\Support\LandMeasure::marlaFromAkms(
            (int) ($acre ?? 0),
            (int) ($kanal ?? 0),
            (int) ($marla ?? 0),
            (int) ($sqft ?? 0),
        );
    }

    $totals = \App\Support\LandMeasure::unitTotalsFromMarla($sourceMarla);
    $tags = [
        ['key' => 'acre', 'value' => $totals['acre'], 'label' => 'Total Acre'],
        ['key' => 'kanal', 'value' => $totals['kanal'], 'label' => 'Total Kanal'],
        ['key' => 'marla', 'value' => $totals['marla'], 'label' => 'Total Marla'],
        ['key' => 'sqft', 'value' => $totals['sqft'], 'label' => 'Total Sqft'],
    ];
@endphp

@once
    <style>
        .land-akms-tags {
            display: flex;
            flex-wrap: wrap;
            gap: 0.35rem;
            align-items: center;
        }
        .land-akms-tag {
            display: inline-flex;
            align-items: baseline;
            gap: 0.28rem;
            padding: 0.18rem 0.5rem;
            border-radius: 999px;
            border: 1px solid var(--border-dark, #cbd5e1);
            background: rgba(15, 23, 42, 0.03);
            font-size: 0.72rem;
            line-height: 1.25;
            color: var(--text-muted, #64748b);
            white-space: nowrap;
        }
        .land-akms-tag__value {
            font-weight: 700;
            font-variant-numeric: tabular-nums;
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
            color: var(--text-body, #0f172a);
        }
        .land-akms-tag.is-zero {
            opacity: 0.55;
        }
    </style>
@endonce

<div {{ $attributes->class(['land-akms-tags']) }} data-akms-tags data-akms-mode="totals">
    @foreach($tags as $tag)
        @php
            $display = \App\Support\LandMeasure::formatUnitTotal((float) $tag['value'], (int) $decimals);
            $isZero = abs((float) $tag['value']) < 1e-9;
        @endphp
        @if($showZero || ! $isZero)
            <span class="land-akms-tag {{ $isZero ? 'is-zero' : '' }}" data-akms-tag-wrap="{{ $tag['key'] }}">
                <span class="land-akms-tag__value" data-akms-tag="{{ $tag['key'] }}">{{ $display }}</span>
                <span>{{ $tag['label'] }}</span>
            </span>
        @endif
    @endforeach
</div>
