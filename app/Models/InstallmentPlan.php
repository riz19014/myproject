<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InstallmentPlan extends Model
{
    protected $fillable = [
        'plot_booking_id',
        'name',
        'installment_count',
        'total_amount',
    ];

    protected function casts(): array
    {
        return [
            'total_amount' => 'decimal:2',
        ];
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(PlotBooking::class, 'plot_booking_id');
    }

    public function dues(): HasMany
    {
        return $this->hasMany(InstallmentDue::class)->orderBy('sequence');
    }
}
