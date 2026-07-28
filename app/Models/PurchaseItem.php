<?php

namespace App\Models;

use App\Support\LandMeasure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseItem extends Model
{
    protected $fillable = [
        'project_id',
        'purchase_file_id',
        'party_id',
        'moza',
        'khasra',
        'khewat_no',
        'khatooni_no',
        'intiqal_no',
        'area_acre',
        'area_kanal',
        'area_marla',
        'area_sqft',
        'land_area_marla',
        'amount_per_acre',
        'line_total_rs',
    ];

    protected function casts(): array
    {
        return [
            'land_area_marla' => 'decimal:4',
            'amount_per_acre' => 'decimal:2',
            'line_total_rs' => 'decimal:2',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function purchaseFile(): BelongsTo
    {
        return $this->belongsTo(PurchaseFile::class);
    }

    public function party(): BelongsTo
    {
        return $this->belongsTo(Party::class);
    }

    public static function acresFromMarla(float $marla): float
    {
        return $marla / 160.0;
    }

    public function hasStoredAkms(): bool
    {
        return (int) $this->area_acre > 0
            || (int) $this->area_kanal > 0
            || (int) $this->area_marla > 0
            || (int) $this->area_sqft > 0;
    }

    public function landAreaMarlaFromAkms(): float
    {
        return LandMeasure::marlaFromAkms(
            (int) $this->area_acre,
            (int) $this->area_kanal,
            (int) $this->area_marla,
            (int) $this->area_sqft,
        );
    }

    public function effectiveLandAreaMarla(): float
    {
        if ($this->hasStoredAkms()) {
            return $this->landAreaMarlaFromAkms();
        }

        return (float) $this->land_area_marla;
    }

    public function landAreaLabel(): string
    {
        if ($this->hasStoredAkms()) {
            return LandMeasure::formatAkmsLabel(
                (int) $this->area_acre,
                (int) $this->area_kanal,
                (int) $this->area_marla,
                (int) $this->area_sqft,
            );
        }

        return LandMeasure::formatAkmsLabelFromMarla((float) $this->land_area_marla);
    }

    /**
     * @param  iterable<int, self>  $items
     */
    public static function sumEffectiveMarla(iterable $items): float
    {
        $sum = 0.0;
        foreach ($items as $item) {
            $sum += $item->effectiveLandAreaMarla();
        }

        return round($sum, 6);
    }
}
