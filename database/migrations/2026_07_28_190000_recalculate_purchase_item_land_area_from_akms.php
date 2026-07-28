<?php

use App\Models\PurchaseItem;
use App\Support\LandMeasure;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        PurchaseItem::query()->orderBy('id')->chunkById(200, function ($items): void {
            foreach ($items as $item) {
                $acre = (int) $item->area_acre;
                $kanal = (int) $item->area_kanal;
                $marla = (int) $item->area_marla;
                $sqft = (int) $item->area_sqft;

                if ($acre === 0 && $kanal === 0 && $marla === 0 && $sqft === 0) {
                    continue;
                }

                $totalMarla = round(LandMeasure::marlaFromAkms($acre, $kanal, $marla, $sqft), 4);
                $lineTotal = round(PurchaseItem::acresFromMarla($totalMarla) * (float) $item->amount_per_acre, 2);

                if (
                    (float) $item->land_area_marla !== $totalMarla
                    || (float) $item->line_total_rs !== $lineTotal
                ) {
                    $item->update([
                        'land_area_marla' => $totalMarla,
                        'line_total_rs' => $lineTotal,
                    ]);
                }
            }
        });
    }

    public function down(): void
    {
        // Irreversible: previous marla values used the old sq-ft-per-marla standard.
    }
};
