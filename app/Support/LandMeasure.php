<?php

namespace App\Support;

final class LandMeasure
{
    /** Punjab-style: 1 kanal = 20 marla, 1 acre = 8 kanal. 1 marla ≈ 272.25 sq ft. */
    public static function marlaFromAkms(int $acre, int $kanal, int $marla, int $sqft): float
    {
        return $acre * 160 + $kanal * 20 + $marla + $sqft / 272.25;
    }

    /** Display total marla as A — K — M — SQFT (decomposed + fractional marla as sq ft). */
    public static function formatAkmsLabelFromMarla(float $totalMarla): string
    {
        $eps = 1e-6;
        if ($totalMarla <= 0 && $totalMarla > -$eps) {
            return 'A 0 — K 0 — M 0 — SQFT 0';
        }
        $wholeMarla = (int) floor($totalMarla + $eps);
        $a = intdiv($wholeMarla, 160);
        $r = $wholeMarla - $a * 160;
        $k = intdiv($r, 20);
        $m = $r - $k * 20;
        $frac = $totalMarla - $wholeMarla;
        if ($frac < 0) {
            $frac = 0;
        }
        $sqft = $frac > $eps ? (int) round($frac * 272.25) : 0;

        return sprintf('A %d — K %d — M %d — SQFT %d', $a, $k, $m, $sqft);
    }

    public static function toMarla(float $amount, string $unit): float
    {
        return match ($unit) {
            'marla' => $amount,
            'kanal' => $amount * 20,
            'acre' => $amount * 160,
            'sqft' => $amount / 272.25,
            default => 0.0,
        };
    }

    public static function formatAmountUnit(float $amount, string $unit): string
    {
        $u = match ($unit) {
            'acre' => 'acre',
            'kanal' => 'kanal',
            'marla' => 'marla',
            'sqft' => 'sq ft',
            default => $unit,
        };
        $decimals = fmod($amount, 1.0) === 0.0 ? 0 : 2;
        $n = number_format($amount, $decimals, '.', '');

        return $n.' '.$u;
    }

    /** Human-readable collective total for read-only UI. */
    public static function formatMarlaTotal(float $marla): string
    {
        if ($marla <= 0) {
            return $marla === 0.0 ? '0 marla (collective)' : '';
        }
        $kanal = $marla / 20;
        $marlaFmt = rtrim(rtrim(number_format($marla, 2, '.', ''), '0'), '.').' marla';
        $kanalFmt = rtrim(rtrim(number_format($kanal, 2, '.', ''), '0'), '.');

        return $marlaFmt.' (collective, ≈ '.$kanalFmt.' kanal)';
    }

    /**
     * @param  iterable<int, \App\Models\Party>  $parties  Parties with pivot land_area / land_area_unit.
     */
    public static function partiesTotalMarla(iterable $parties): float
    {
        $sum = 0.0;
        foreach ($parties as $party) {
            $a = $party->pivot->land_area ?? null;
            $u = $party->pivot->land_area_unit ?? null;
            if ($a === null || $u === null || $u === '') {
                continue;
            }
            $sum += self::toMarla((float) $a, (string) $u);
        }

        return round($sum, 4);
    }

    /**
     * @return array{party_areas: array<string, array{land_area: float, land_area_unit: string, label: string}>, parties_total_marla: float, parties_total_label: string}
     */
    public static function projectPartyAreaPayload(\App\Models\Project $project): array
    {
        $project->loadMissing('parties');
        $partyAreas = [];
        foreach ($project->parties as $party) {
            $a = $party->pivot->land_area ?? null;
            $u = $party->pivot->land_area_unit ?? null;
            if ($a === null || $u === null || $u === '') {
                continue;
            }
            $af = (float) $a;
            $unitStr = (string) $u;
            $label = $unitStr === 'marla'
                ? self::formatAkmsLabelFromMarla($af)
                : self::formatAmountUnit($af, $unitStr);
            $partyAreas[(string) $party->id] = [
                'land_area' => $af,
                'land_area_unit' => $unitStr,
                'label' => $label,
            ];
        }
        $totalMarla = self::partiesTotalMarla($project->parties);

        return [
            'party_areas' => $partyAreas,
            'parties_total_marla' => $totalMarla,
            'parties_total_label' => $totalMarla > 0 ? self::formatMarlaTotal($totalMarla) : '',
        ];
    }
}
