<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    /** Default calendar month/year when opening Daybook without `?date=` (value: `YYYY-MM`, day implied as 1st). */
    public const CODE_DAYBOOK_DEFAULT_MONTH_YEAR = 'daybook_default_month_year';

    protected $fillable = [
        'code',
        'value',
    ];

    public static function getValue(string $code, ?string $default = null): ?string
    {
        $row = static::query()->where('code', $code)->first();

        return $row?->value ?? $default;
    }

    public static function put(string $code, ?string $value): void
    {
        if ($value === null || $value === '') {
            static::query()->where('code', $code)->delete();

            return;
        }

        static::query()->updateOrCreate(
            ['code' => $code],
            ['value' => $value]
        );
    }

    /** First day of the configured month, or null to fall back to “today”. */
    public static function daybookDefaultCalendarDay(): ?Carbon
    {
        $raw = static::getValue(self::CODE_DAYBOOK_DEFAULT_MONTH_YEAR);
        if ($raw === null || $raw === '') {
            return null;
        }
        $raw = trim($raw);
        if (! preg_match('/^\d{4}-\d{2}$/', $raw)) {
            return null;
        }
        try {
            return Carbon::createFromFormat('Y-m', $raw)->startOfMonth()->startOfDay();
        } catch (\Throwable) {
            return null;
        }
    }
}
