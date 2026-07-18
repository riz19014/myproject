<?php

namespace App\Support;

final class LandPrecision
{
    /** Decimal places of land area columns (decimal(15,4)). */
    public const STORAGE_DECIMALS = 4;

    /** Round a marla value to the precision used by land area columns. */
    public static function forStorage(float $marla): float
    {
        return round($marla, self::STORAGE_DECIMALS);
    }
}
