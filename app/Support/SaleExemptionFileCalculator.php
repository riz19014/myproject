<?php

namespace App\Support;

use App\Models\ProjectSaleExemptionComponent;
use App\Models\ProjectSaleExemptionPlotType;

/**
 * Exempt plot file count from total land area.
 * files = (marla_per_plot × acres) ÷ nominal_marla
 * acres = total_marla ÷ marla_per_acre (e.g. 560 marla ÷ 160 = 3.5 acres).
 */
final class SaleExemptionFileCalculator
{
    public static function acresFromMarla(float $totalMarla, float $marlaPerAcre): float
    {
        if ($marlaPerAcre <= 0) {
            return 0.0;
        }

        return round($totalMarla / $marlaPerAcre, 4);
    }

    public static function nominalMarlaForSlug(string $slug, ?float $fallback = null): float
    {
        return match ($slug) {
            '2_kanal' => 40.0,
            '1_kanal' => 20.0,
            '10_marla' => 10.0,
            '8_marla' => 8.0,
            default => $fallback ?? 0.0,
        };
    }

    public static function nominalMarlaForPlot(ProjectSaleExemptionPlotType $plot): float
    {
        if ($plot->nominal_marla !== null && (float) $plot->nominal_marla > 0) {
            return (float) $plot->nominal_marla;
        }

        return self::nominalMarlaForSlug($plot->slug, (float) $plot->marla_per_plot);
    }

    /**
     * @return array{
     *   total_marla: float,
     *   acres: float,
     *   marla_per_acre: float,
     *   rows: list<array{
     *     code: string,
     *     component_slug: string,
     *     component_label: string,
     *     plot_slug: string,
     *     plot_label: string,
     *     share_percent: float,
     *     marla_per_plot: float,
     *     nominal_marla: float,
     *     product_marla: float,
     *     file_count: float,
     *     full_files: int,
     *     fraction_files: float,
     *     fraction_marla: float,
     *     whole_files_marla: float,
     *     total_line_marla: float
     *   }>
     * }
     */
    public static function calculate(float $totalMarla, SaleExemptionConfig $config): array
    {
        $marlaPerAcre = $config->marlaPerAcreLand();
        $acres = self::acresFromMarla($totalMarla, $marlaPerAcre);
        $rows = [];
        $globalIndex = 0;

        foreach ($config->components() as $component) {
            $prefix = self::componentCodePrefix($component);
            foreach ($component->plotTypes as $plot) {
                $globalIndex++;
                $marlaPerPlot = (float) $plot->marla_per_plot;
                $nominal = self::nominalMarlaForPlot($plot);
                $product = round($marlaPerPlot * $acres, 4);
                $fileCount = $nominal > 0 ? round($product / $nominal, 4) : 0.0;
                $fullFiles = (int) floor($fileCount + 1e-9);
                $fractionFiles = round($fileCount - $fullFiles, 4);
                $fractionMarla = round($fractionFiles * $nominal, 4);
                $wholeFilesMarla = round($fullFiles * $marlaPerPlot, 4);
                $totalLineMarla = round($wholeFilesMarla + $fractionMarla, 4);

                $rows[] = [
                    'code' => $prefix.'.'.$globalIndex,
                    'component_slug' => $component->slug,
                    'component_label' => $component->label,
                    'plot_slug' => $plot->slug,
                    'plot_label' => $plot->label,
                    'share_percent' => (float) $plot->share_percent,
                    'marla_per_plot' => $marlaPerPlot,
                    'nominal_marla' => $nominal,
                    'product_marla' => $product,
                    'file_count' => $fileCount,
                    'full_files' => $fullFiles,
                    'fraction_files' => $fractionFiles,
                    'fraction_marla' => $fractionMarla,
                    'whole_files_marla' => $wholeFilesMarla,
                    'total_line_marla' => $totalLineMarla,
                ];
            }
        }

        return [
            'total_marla' => round($totalMarla, 4),
            'acres' => $acres,
            'marla_per_acre' => $marlaPerAcre,
            'rows' => $rows,
        ];
    }

    private static function componentCodePrefix(ProjectSaleExemptionComponent $component): string
    {
        return strtoupper(substr($component->slug, 0, 1));
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     */
    /** Marla value for display (avoids 40 → "4M" when trailing zero stripped). */
    public static function formatMarla(float $marla): string
    {
        $rounded = round($marla, 4);
        if (abs($rounded - (int) $rounded) < 1e-9) {
            return (string) (int) $rounded;
        }

        return rtrim(rtrim(number_format($rounded, 4, '.', ''), '0'), '.');
    }

    public static function formatMarlaWithUnit(float $marla): string
    {
        return self::formatMarla($marla).'M';
    }

    public static function formatFileCount(float $count): string
    {
        return self::formatMarla($count);
    }

    public static function formatFileBreakdown(int $fullFiles, float $fractionFiles, float $fractionMarla): string
    {
        if ($fullFiles < 1 && $fractionFiles <= 0) {
            return '—';
        }
        $parts = [];
        if ($fullFiles > 0) {
            $parts[] = $fullFiles.' file'.($fullFiles === 1 ? '' : 's');
        }
        if ($fractionFiles > 0) {
            $parts[] = self::formatMarla($fractionFiles).' file + '.self::formatMarlaWithUnit($fractionMarla);
        }

        return implode(' + ', $parts);
    }
}
