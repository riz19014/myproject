<?php

namespace App\Support;

use App\Models\DayBookEntry;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

final class DaybookVoucher
{
    public static function display(?int $voucherNo): string
    {
        return $voucherNo !== null ? (string) $voucherNo : '—';
    }

    public static function nextForDate(CarbonInterface $date): int
    {
        $year = (int) $date->format('y');
        $base = $year * 10000;
        $max = DayBookEntry::query()
            ->where('voucher_no', '>', $base)
            ->where('voucher_no', '<', $base + 10000)
            ->max('voucher_no');

        return $max ? ((int) $max) + 1 : $base + 1;
    }

    public static function assignIfMissing(DayBookEntry $entry): DayBookEntry
    {
        if ($entry->voucher_no !== null) {
            return $entry;
        }

        return DB::transaction(function () use ($entry) {
            $locked = DayBookEntry::query()->lockForUpdate()->findOrFail($entry->id);

            if ($locked->voucher_no !== null) {
                return $locked;
            }

            $locked->voucher_no = self::nextForDate($locked->entry_date);
            $locked->save();

            return $locked;
        });
    }
}
