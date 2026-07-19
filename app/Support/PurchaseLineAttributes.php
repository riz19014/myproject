<?php

namespace App\Support;

use App\Models\PurchaseItem;
use Illuminate\Validation\ValidationException;

final class PurchaseLineAttributes
{
    /**
     * @param  array<string, mixed>  $line
     * @return array<string, mixed>
     */
    public static function fromInput(array $line, string $areaErrorKey, ?string $areaErrorMessage = null): array
    {
        $marla = LandMeasure::marlaFromAkms(
            (int) ($line['area_acre'] ?? 0),
            (int) ($line['area_kanal'] ?? 0),
            (int) ($line['area_marla'] ?? 0),
            (int) ($line['area_sqft'] ?? 0),
        );
        if ($marla <= 0) {
            throw ValidationException::withMessages([
                $areaErrorKey => [$areaErrorMessage ?? 'Enter at least one positive whole number in Acre, Kanal, Marla, or Sq ft.'],
            ]);
        }
        $acres = PurchaseItem::acresFromMarla($marla);
        $amountPerAcre = round((float) $line['amount_per_acre'], 2);
        $lineTotal = round($acres * $amountPerAcre, 2);

        return [
            'party_id' => (int) $line['party_id'],
            'moza' => $line['moza'] ?? null,
            'khasra' => $line['khasra'] ?? null,
            'khewat_no' => $line['khewat_no'] ?? null,
            'khatooni_no' => $line['khatooni_no'] ?? null,
            'intiqal_no' => $line['intiqal_no'] ?? null,
            'area_acre' => (int) $line['area_acre'],
            'area_kanal' => (int) $line['area_kanal'],
            'area_marla' => (int) $line['area_marla'],
            'area_sqft' => (int) $line['area_sqft'],
            'land_area_marla' => round($marla, 4),
            'amount_per_acre' => $amountPerAcre,
            'line_total_rs' => $lineTotal,
        ];
    }
}
