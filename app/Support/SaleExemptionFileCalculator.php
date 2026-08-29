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
     *     total_line_marla: float,
     *     sale_code: string,
     *     rate_per_file: float|null,
     *     amount_per_file: float|null,
     *     line_sale_amount: float|null
     *   }>
     * }
     */
    public static function calculate(float $totalMarla, SaleExemptionConfig $config, array $ratesPerFile = []): array
    {
        $marlaPerAcre = $config->marlaPerAcreLand();
        $acres = self::acresFromMarla($totalMarla, $marlaPerAcre);
        $rows = [];
        $globalIndex = 0;
        $residentialIndex = 0;

        foreach ($config->components() as $component) {
            $prefix = self::componentCodePrefix($component);
            foreach ($component->plotTypes as $plot) {
                $globalIndex++;
                if ($component->slug === SaleExemptionRules::COMPONENT_RESIDENTIAL) {
                    $residentialIndex++;
                }
                $marlaPerPlot = (float) $plot->marla_per_plot;
                $nominal = self::nominalMarlaForPlot($plot);
                $product = round($marlaPerPlot * $acres, 4);
                $fileCount = $nominal > 0 ? round($product / $nominal, 4) : 0.0;
                $fullFiles = (int) floor($fileCount + 1e-9);
                $fractionFiles = round($fileCount - $fullFiles, 4);
                $fractionMarla = round($fractionFiles * $nominal, 4);
                $wholeFilesMarla = round($fullFiles * $marlaPerPlot, 4);
                $totalLineMarla = round($wholeFilesMarla + $fractionMarla, 4);
                $ratePerFile = (float) ($ratesPerFile[$plot->slug] ?? 0);
                $amountPerFile = null;
                $lineSaleAmount = null;
                if ($ratePerFile > 0) {
                    $amountPerFile = round($ratePerFile, 2);
                    $lineSaleAmount = round($amountPerFile * $fileCount, 2);
                }

                $rows[] = [
                    'code' => $prefix.'.'.$globalIndex,
                    'sale_code' => self::plotSaleCode($component->slug, $residentialIndex),
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
                    'rate_per_file' => $ratePerFile > 0 ? $ratePerFile : null,
                    'amount_per_file' => $amountPerFile,
                    'line_sale_amount' => $lineSaleAmount,
                ];
            }
        }

        return [
            'total_marla' => round($totalMarla, 4),
            'acres' => $acres,
            'marla_per_acre' => $marlaPerAcre,
            'rows' => $rows,
            'total_sale_amount' => self::sumLineSaleAmounts($rows),
        ];
    }

    public static function plotSaleCode(string $componentSlug, int $residentialIndex): string
    {
        if ($componentSlug === SaleExemptionRules::COMPONENT_COMMERCIAL) {
            return 'Commercial';
        }

        return 'R'.$residentialIndex;
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     */
    public static function sumLineSaleAmounts(array $rows): ?float
    {
        $total = 0.0;
        $hasAmount = false;
        foreach ($rows as $row) {
            if ($row['line_sale_amount'] === null) {
                continue;
            }
            $total += (float) $row['line_sale_amount'];
            $hasAmount = true;
        }

        return $hasAmount ? round($total, 2) : null;
    }

    public static function formatRs(?float $amount): string
    {
        if ($amount === null) {
            return '—';
        }

        return number_format($amount, 0);
    }

    public static function formatRsWithWords(?float $amount): string
    {
        if ($amount === null) {
            return '—';
        }

        $words = self::formatRsWords($amount);

        return 'Rs '.number_format($amount, 0).($words !== '' ? ' ('.$words.')' : '');
    }

    public static function formatRsWords(?float $amount): string
    {
        if ($amount === null || $amount <= 0) {
            return '';
        }

        $intPart = (int) round($amount);
        if ($intPart >= 10000000) {
            return self::scaleAmountWords($intPart / 10000000).' crore';
        }
        if ($intPart >= 100000) {
            return self::scaleAmountWords($intPart / 100000).' lac';
        }
        if ($intPart >= 1000) {
            return self::scaleAmountWords($intPart / 1000).' thousand';
        }

        return '';
    }

    /** Compact amount label for UI, e.g. 2.30 cr / 5.50 lac. */
    public static function formatRsShort(?float $amount): string
    {
        if ($amount === null || $amount <= 0) {
            return '';
        }

        $intPart = (int) round($amount);
        if ($intPart >= 10000000) {
            return number_format($intPart / 10000000, 2, '.', '').' cr';
        }
        if ($intPart >= 100000) {
            return number_format($intPart / 100000, 2, '.', '').' lac';
        }
        if ($intPart >= 1000) {
            return number_format($intPart / 1000, 2, '.', '').' th';
        }

        return '';
    }

    private static function scaleAmountWords(float $value): string
    {
        return rtrim(rtrim(number_format($value, 2, '.', ''), '0'), '.');
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

    /** Whole marla from a decimal-in-marla value (e.g. 11.5 → 11). */
    public static function wholeMarlaPart(float $marla): int
    {
        if ($marla <= 0) {
            return 0;
        }

        return (int) floor($marla + 1e-9);
    }

    /** Fractional marla after the whole part (e.g. 11.5 → 0.5). */
    public static function fractionalMarlaPart(float $marla): float
    {
        if ($marla <= 0) {
            return 0.0;
        }

        $frac = $marla - self::wholeMarlaPart($marla);

        return $frac > 1e-9 ? round($frac, 4) : 0.0;
    }

    /**
     * Remaining fraction of marla as sq ft (1 marla = 225 sq ft).
     * e.g. 11.5M → 0.5M → 112.5 SQFT
     */
    public static function remainderSqftFromMarla(float $marla): float
    {
        $frac = self::fractionalMarlaPart($marla);
        if ($frac <= 0) {
            return 0.0;
        }

        return round($frac * LandMeasure::SQFT_PER_MARLA, 4);
    }

    public static function formatSqft(float $sqft): string
    {
        if ($sqft <= 0) {
            return '—';
        }

        $rounded = round($sqft, 4);
        if (abs($rounded - (int) $rounded) < 1e-9) {
            return ((int) $rounded).' SQFT';
        }

        return rtrim(rtrim(number_format($rounded, 4, '.', ''), '0'), '.').' SQFT';
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
