<?php

namespace App\Models;

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
}
