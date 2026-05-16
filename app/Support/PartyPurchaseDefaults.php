<?php

namespace App\Support;

use App\Models\PurchaseItem;

final class PartyPurchaseDefaults
{
    /**
     * Distinct Moza values already saved on purchase lines (for autocomplete).
     *
     * @return list<string>
     */
    public static function distinctMozas(): array
    {
        return PurchaseItem::query()
            ->whereNotNull('moza')
            ->where('moza', '!=', '')
            ->distinct()
            ->orderBy('moza')
            ->pluck('moza')
            ->map(fn ($m) => (string) $m)
            ->values()
            ->all();
    }
}
