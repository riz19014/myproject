<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BoqItem extends Model
{
    protected $fillable = [
        'development_package_id',
        'item_name',
        'unit',
        'quantity',
        'unit_rate',
        'estimated_amount',
        'actual_amount',
        'notes',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:4',
            'unit_rate' => 'decimal:2',
            'estimated_amount' => 'decimal:2',
            'actual_amount' => 'decimal:2',
        ];
    }

    public function package(): BelongsTo
    {
        return $this->belongsTo(DevelopmentPackage::class, 'development_package_id');
    }

    public function recalculateEstimated(): void
    {
        $this->estimated_amount = round((float) $this->quantity * (float) $this->unit_rate, 2);
    }
}
