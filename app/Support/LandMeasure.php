<?php

namespace App\Support;

/**
 * Configurable land area math.
 *
 * Canonical internal unit: marla (decimal). All conversions go through marla
 * so precision is preserved and display units can change without rewriting storage.
 */
final class LandMeasure
{
    public const UNIT_ACRE = 'acre';

    public const UNIT_KANAL = 'kanal';

    public const UNIT_MARLA = 'marla';

    public const UNIT_SQFT = 'sqft';

    public const UNIT_AKMS = 'akms';

    /** @deprecated Prefer sqftPerMarla() — kept for older call sites. */
    public const SQFT_PER_MARLA = 225.0;

    public static function kanalPerAcre(): float
    {
        return (float) config('land_measure.kanal_per_acre', 8);
    }

    public static function marlaPerKanal(): float
    {
        return (float) config('land_measure.marla_per_kanal', 20);
    }

    public static function sqftPerMarla(): float
    {
        return (float) config('land_measure.sqft_per_marla', 225.0);
    }

    public static function marlaPerAcre(): float
    {
        return self::kanalPerAcre() * self::marlaPerKanal();
    }

    /**
     * Ratios for JS / API payloads.
     *
     * @return array{kanal_per_acre: float, marla_per_kanal: float, sqft_per_marla: float, marla_per_acre: float, default_display_unit: string}
     */
    public static function conversionConfig(): array
    {
        return [
            'kanal_per_acre' => self::kanalPerAcre(),
            'marla_per_kanal' => self::marlaPerKanal(),
            'sqft_per_marla' => self::sqftPerMarla(),
            'marla_per_acre' => self::marlaPerAcre(),
            'default_display_unit' => (string) config('land_measure.default_display_unit', self::UNIT_ACRE),
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function displayUnits(): array
    {
        /** @var array<string, string> $units */
        $units = config('land_measure.display_units', [
            self::UNIT_ACRE => 'Acre (A)',
            self::UNIT_KANAL => 'Kanal (K)',
            self::UNIT_MARLA => 'Marla (M)',
            self::UNIT_SQFT => 'Sqft',
        ]);

        return $units;
    }

    public static function normalizeDisplayUnit(?string $unit): string
    {
        $unit = strtolower(trim((string) $unit));
        if ($unit === self::UNIT_AKMS || $unit === '') {
            return self::UNIT_ACRE;
        }

        return match ($unit) {
            self::UNIT_ACRE, self::UNIT_KANAL, self::UNIT_MARLA, self::UNIT_SQFT => $unit,
            default => (string) config('land_measure.default_display_unit', self::UNIT_ACRE),
        };
    }

    /** Mixed A/K/M/Sqft → canonical marla (exact). */
    public static function marlaFromAkms(int|float $acre, int|float $kanal, int|float $marla, int|float $sqft): float
    {
        return ((float) $acre * self::marlaPerAcre())
            + ((float) $kanal * self::marlaPerKanal())
            + (float) $marla
            + ((float) $sqft / self::sqftPerMarla());
    }

    /** Convert any single-unit amount into marla. */
    public static function toMarla(float $amount, string $unit): float
    {
        return match (self::normalizeDisplayUnit($unit)) {
            self::UNIT_MARLA => $amount,
            self::UNIT_KANAL => $amount * self::marlaPerKanal(),
            self::UNIT_ACRE => $amount * self::marlaPerAcre(),
            self::UNIT_SQFT => $amount / self::sqftPerMarla(),
            default => 0.0,
        };
    }

    /** Convert marla into another unit (decimal, no rounding). */
    public static function fromMarla(float $marla, string $unit): float
    {
        return match (self::normalizeDisplayUnit($unit)) {
            self::UNIT_MARLA => $marla,
            self::UNIT_KANAL => $marla / self::marlaPerKanal(),
            self::UNIT_ACRE => $marla / self::marlaPerAcre(),
            self::UNIT_SQFT => $marla * self::sqftPerMarla(),
            default => $marla,
        };
    }

    /** Convert amount from one unit to another via marla. */
    public static function convert(float $amount, string $fromUnit, string $toUnit): float
    {
        return self::fromMarla(self::toMarla($amount, $fromUnit), $toUnit);
    }

    /**
     * Decompose total marla into residual A / K / M / SQFT parts (acre-led).
     *
     * @return array{acre: int, kanal: int, marla: int, sqft: int}
     */
    public static function akmsPartsFromMarla(float $totalMarla): array
    {
        return self::partsFromMarla($totalMarla, self::UNIT_ACRE);
    }

    /**
     * Decompose total marla for a display mode.
     *
     * acre  → A, K, M, SQFT
     * kanal → K, M, SQFT (acres folded into kanal)  e.g. A1 K2 … → K10 …
     * marla → M, SQFT
     * sqft  → SQFT only
     *
     * @return array{acre: int, kanal: int, marla: int, sqft: int}
     */
    public static function partsFromMarla(float $totalMarla, string $displayUnit = self::UNIT_ACRE): array
    {
        $displayUnit = self::normalizeDisplayUnit($displayUnit);
        $eps = 1e-9;
        if ($totalMarla <= 0 && $totalMarla > -$eps) {
            return ['acre' => 0, 'kanal' => 0, 'marla' => 0, 'sqft' => 0];
        }

        $sign = $totalMarla < 0 ? -1 : 1;
        $abs = abs($totalMarla);
        $marlaPerKanal = self::marlaPerKanal();
        $marlaPerAcre = self::marlaPerAcre();
        $sqftPerMarla = self::sqftPerMarla();

        $wholeMarla = (int) floor($abs + $eps);
        $frac = max(0.0, $abs - $wholeMarla);
        $sqft = $frac > $eps ? (int) round($frac * $sqftPerMarla) : 0;

        if ($displayUnit === self::UNIT_SQFT) {
            $totalSqft = (int) round($abs * $sqftPerMarla);

            return [
                'acre' => 0,
                'kanal' => 0,
                'marla' => 0,
                'sqft' => $sign * $totalSqft,
            ];
        }

        if ($displayUnit === self::UNIT_MARLA) {
            return [
                'acre' => 0,
                'kanal' => 0,
                'marla' => $sign * $wholeMarla,
                'sqft' => $sign * $sqft,
            ];
        }

        if ($displayUnit === self::UNIT_KANAL) {
            $k = (int) intdiv($wholeMarla, (int) $marlaPerKanal);
            $m = $wholeMarla - ($k * (int) $marlaPerKanal);

            return [
                'acre' => 0,
                'kanal' => $sign * $k,
                'marla' => $sign * $m,
                'sqft' => $sign * $sqft,
            ];
        }

        // acre / akms
        $a = (int) intdiv($wholeMarla, (int) $marlaPerAcre);
        $r = $wholeMarla - ($a * (int) $marlaPerAcre);
        $k = (int) intdiv($r, (int) $marlaPerKanal);
        $m = $r - ($k * (int) $marlaPerKanal);

        return [
            'acre' => $sign * $a,
            'kanal' => $sign * $k,
            'marla' => $sign * $m,
            'sqft' => $sign * $sqft,
        ];
    }

    /** Display stored A/K/M/SQFT components exactly as entered (no marla round-trip). */
    public static function formatAkmsLabel(int $acre, int $kanal, int $marla, int $sqft): string
    {
        return sprintf('A %d — K %d — M %d — SQFT %d', $acre, $kanal, $marla, $sqft);
    }

    /**
     * Format a marla total for a display unit (acre / kanal / marla / sqft).
     * Preserves mixed residual parts where applicable.
     */
    public static function formatLabelFromMarla(float $totalMarla, string $displayUnit = self::UNIT_ACRE): string
    {
        $displayUnit = self::normalizeDisplayUnit($displayUnit);
        $p = self::partsFromMarla($totalMarla, $displayUnit);
        $neg = $totalMarla < -1e-9;

        return match ($displayUnit) {
            self::UNIT_SQFT => ($neg ? '−' : '').sprintf('SQFT %d', abs($p['sqft'])),
            self::UNIT_MARLA => ($neg ? '−' : '').sprintf('M %d — SQFT %d', abs($p['marla']), abs($p['sqft'])),
            self::UNIT_KANAL => ($neg ? '−' : '').sprintf('K %d — M %d — SQFT %d', abs($p['kanal']), abs($p['marla']), abs($p['sqft'])),
            default => ($neg ? '−' : '').sprintf(
                'A %d — K %d — M %d — SQFT %d',
                abs($p['acre']),
                abs($p['kanal']),
                abs($p['marla']),
                abs($p['sqft'])
            ),
        };
    }

    /** Display total marla as A — K — M — SQFT (decomposed + fractional marla as sq ft). */
    public static function formatAkmsLabelFromMarla(float $totalMarla): string
    {
        return self::formatLabelFromMarla($totalMarla, self::UNIT_ACRE);
    }

    /**
     * Whole-line equivalents of a marla total (not residual A/K/M/SQFT parts).
     *
     * @return array{acre: float, kanal: float, marla: float, sqft: float}
     */
    public static function unitTotalsFromMarla(float $totalMarla): array
    {
        return [
            'acre' => $totalMarla / self::marlaPerAcre(),
            'kanal' => $totalMarla / self::marlaPerKanal(),
            'marla' => $totalMarla,
            'sqft' => $totalMarla * self::sqftPerMarla(),
        ];
    }

    public static function formatUnitTotal(float $amount, int $decimals = 2): string
    {
        if (abs($amount) < 1e-9) {
            return '0';
        }
        if (abs($amount - round($amount)) < 1e-9) {
            return number_format((float) round($amount), 0, '.', '');
        }

        return rtrim(rtrim(number_format($amount, $decimals, '.', ''), '0'), '.');
    }

    /** Compact dashed label for summary strips, e.g. A-8-4K-14M */
    public static function formatAkmsCompactFromMarla(float $totalMarla): string
    {
        $eps = 1e-6;
        if ($totalMarla <= 0 && $totalMarla > -$eps) {
            return 'A-0-0K-0M';
        }
        $p = self::partsFromMarla($totalMarla, self::UNIT_ACRE);
        $base = sprintf('A-%d-%dK-%dM', abs($p['acre']), abs($p['kanal']), abs($p['marla']));
        if (abs($p['sqft']) > 0) {
            return $base.'-'.abs($p['sqft']).'SQFT';
        }

        return $base;
    }

    public static function formatAmountUnit(float $amount, string $unit): string
    {
        $u = match (self::normalizeDisplayUnit($unit)) {
            self::UNIT_ACRE => 'acre',
            self::UNIT_KANAL => 'kanal',
            self::UNIT_MARLA => 'marla',
            self::UNIT_SQFT => 'sq ft',
            default => $unit,
        };
        $decimals = fmod($amount, 1.0) === 0.0 ? 0 : 2;
        $n = number_format($amount, $decimals, '.', '');

        return $n.' '.$u;
    }

    /**
     * Spoken-style land total for naming a project file (e.g. "23 kanal 5 marla").
     * Uses whole marla only; any fractional remainder is appended as "+ X.XX marla".
     */
    public static function formatSpokenKanalMarlaFromMarla(float $totalMarla): string
    {
        $eps = 1e-6;
        if ($totalMarla <= $eps && $totalMarla >= -$eps) {
            return '0 marla';
        }
        $whole = (int) floor($totalMarla + $eps);
        $frac = max(0.0, $totalMarla - $whole);
        $marlaPerKanal = (int) self::marlaPerKanal();
        $k = intdiv($whole, $marlaPerKanal);
        $m = $whole - $k * $marlaPerKanal;
        $parts = [];
        if ($k > 0) {
            $parts[] = $k.' kanal';
        }
        if ($m > 0) {
            $parts[] = $m.' marla';
        }
        if ($parts === []) {
            $s = '';
        } else {
            $s = implode(' ', $parts);
        }
        if ($frac > 1e-5) {
            $frag = rtrim(rtrim(number_format($frac, 2, '.', ''), '0'), '.');
            $s = $s === '' ? $frag.' marla' : $s.' + '.$frag.' marla';
        }
        if ($s === '') {
            return rtrim(rtrim(number_format($totalMarla, 2, '.', ''), '0'), '.').' marla';
        }

        return $s;
    }

    /** Human-readable collective total for read-only UI. */
    public static function formatMarlaTotal(float $marla): string
    {
        if ($marla <= 0) {
            return $marla === 0.0 ? '0 marla (collective)' : '';
        }
        $kanal = $marla / self::marlaPerKanal();
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
