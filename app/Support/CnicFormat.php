<?php

namespace App\Support;

final class CnicFormat
{
    /** Pakistan CNIC groups: 5 + 7 + 1 = 13 digits (e.g. 23012-2321373-1). */
    private const SEGMENTS = [5, 7, 1];

    public static function digits(?string $value): string
    {
        return substr(preg_replace('/\D/', '', (string) $value), 0, self::totalDigits());
    }

    public static function totalDigits(): int
    {
        return array_sum(self::SEGMENTS);
    }

    public static function display(?string $value): string
    {
        $digits = self::digits($value);
        if ($digits === '') {
            return '';
        }

        $parts = [];
        $offset = 0;
        foreach (self::SEGMENTS as $len) {
            if ($offset >= strlen($digits)) {
                break;
            }
            $chunk = substr($digits, $offset, $len);
            if ($chunk !== '') {
                $parts[] = $chunk;
            }
            $offset += $len;
        }

        return implode('-', $parts);
    }

    public static function isComplete(?string $value): bool
    {
        return strlen(self::digits($value)) === self::totalDigits();
    }
}
