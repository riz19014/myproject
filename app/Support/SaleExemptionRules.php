<?php

namespace App\Support;

use App\Models\ProjectFile;

/**
 * Ph-X style exemption: residential 25% + commercial 3.49% of file land.
 * Residential 40 marla/acre split: 2 Kanal 16, 1 Kanal 14, 10 Marla 10.
 * Commercial: 8 Marla plot = 5.584 marla from pool.
 */
final class SaleExemptionRules
{
    public const RESIDENTIAL_PERCENT = 25.0;

    public const COMMERCIAL_PERCENT = 3.49;

    public const COMPONENT_RESIDENTIAL = 'residential';

    public const COMPONENT_COMMERCIAL = 'commercial';

    /** @var array<string, array{label: string, marla: float, share_percent: float}> */
    public const RESIDENTIAL_PLOTS = [
        '2_kanal' => ['label' => '2 Kanal file', 'marla' => 16.0, 'share_percent' => 40.0],
        '1_kanal' => ['label' => '1 Kanal file', 'marla' => 14.0, 'share_percent' => 35.0],
        '10_marla' => ['label' => '10 Marla file', 'marla' => 10.0, 'share_percent' => 25.0],
    ];

    /** @var array<string, array{label: string, marla: float}> */
    public const COMMERCIAL_PLOTS = [
        '8_marla' => ['label' => '8 Marla file', 'marla' => 5.584],
    ];

    public static function residentialPoolMarla(float $fileMarla, ?float $percent = null): float
    {
        $pct = $percent ?? self::RESIDENTIAL_PERCENT;

        return round($fileMarla * $pct / 100, 4);
    }

    public static function commercialPoolMarla(float $fileMarla, ?float $percent = null): float
    {
        $pct = $percent ?? self::COMMERCIAL_PERCENT;

        return round($fileMarla * $pct / 100, 4);
    }

    /**
     * @return array{residential: float, commercial: float, residential_per_acre: float}
     */
    public static function poolsForFile(
        float $fileMarla,
        ?float $residentialPercent = null,
        ?float $commercialPercent = null
    ): array {
        $acres = $fileMarla > 0 ? $fileMarla / 160.0 : 0.0;

        return [
            'residential' => self::residentialPoolMarla($fileMarla, $residentialPercent),
            'commercial' => self::commercialPoolMarla($fileMarla, $commercialPercent),
            'residential_per_acre' => round(40.0 * $acres, 4),
        ];
    }

    public static function poolsForProjectFile(ProjectFile $file): array
    {
        $config = SaleExemptionConfig::forFile($file);
        $fileMarla = (float) $file->land_area_marla;
        $pools = $config->poolsForFileMarla($fileMarla);
        $acres = $fileMarla > 0 ? $fileMarla / $config->marlaPerAcreLand() : 0.0;
        $resDist = $config->findComponent(self::COMPONENT_RESIDENTIAL)?->distributionMarlaPerAcre() ?? 0;

        return [
            'residential' => $pools[self::COMPONENT_RESIDENTIAL] ?? 0.0,
            'commercial' => $pools[self::COMPONENT_COMMERCIAL] ?? 0.0,
            'residential_per_acre' => round($resDist * $acres, 4),
            'by_component' => $pools,
        ];
    }

    public static function plotMarla(string $component, string $plotType, ?ProjectFile $file = null): float
    {
        if ($file) {
            return SaleExemptionConfig::forFile($file)->plotMarla($component, $plotType);
        }

        if ($component === self::COMPONENT_RESIDENTIAL) {
            return (float) (self::RESIDENTIAL_PLOTS[$plotType]['marla'] ?? 0);
        }
        if ($component === self::COMPONENT_COMMERCIAL) {
            return (float) (self::COMMERCIAL_PLOTS[$plotType]['marla'] ?? 0);
        }

        return 0.0;
    }

    public static function plotLabel(string $component, string $plotType, ?ProjectFile $file = null): string
    {
        if ($file) {
            return SaleExemptionConfig::forFile($file)->plotLabel($component, $plotType);
        }

        if ($component === self::COMPONENT_RESIDENTIAL) {
            return self::RESIDENTIAL_PLOTS[$plotType]['label'] ?? $plotType;
        }
        if ($component === self::COMPONENT_COMMERCIAL) {
            return self::COMMERCIAL_PLOTS[$plotType]['label'] ?? $plotType;
        }

        return $plotType;
    }

    /**
     * @return array<string, string>
     */
    public static function plotOptionsForComponent(string $component, ?ProjectFile $file = null): array
    {
        if ($file) {
            return SaleExemptionConfig::forFile($file)->plotOptionsForComponent($component);
        }

        if ($component === self::COMPONENT_COMMERCIAL) {
            return collect(self::COMMERCIAL_PLOTS)->mapWithKeys(fn ($v, $k) => [$k => $v['label']])->all();
        }

        return collect(self::RESIDENTIAL_PLOTS)->mapWithKeys(fn ($v, $k) => [$k => $v['label']])->all();
    }
}
