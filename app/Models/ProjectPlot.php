<?php

namespace App\Models;

use App\Support\LandMeasure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProjectPlot extends Model
{
    public const STATUS_AVAILABLE = 'available';

    public const STATUS_BOOKED = 'booked';

    public const STATUS_SOLD = 'sold';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUSES = [
        self::STATUS_AVAILABLE => 'Available',
        self::STATUS_BOOKED => 'Booked',
        self::STATUS_SOLD => 'Sold',
        self::STATUS_CANCELLED => 'Cancelled',
    ];

    public const CATEGORIES = [
        'residential' => 'Residential',
        'commercial' => 'Commercial',
        'amenity' => 'Amenity',
    ];

    protected $fillable = [
        'project_id',
        'project_block_id',
        'plot_number',
        'category',
        'area_acre',
        'area_kanal',
        'area_marla',
        'area_sqft',
        'land_area_marla',
        'status',
        'list_price',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'land_area_marla' => 'decimal:6',
            'list_price' => 'decimal:2',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function block(): BelongsTo
    {
        return $this->belongsTo(ProjectBlock::class, 'project_block_id');
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(PlotBooking::class);
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function syncMarlaFromAkms(): void
    {
        $this->land_area_marla = LandMeasure::marlaFromAkms(
            (int) $this->area_acre,
            (int) $this->area_kanal,
            (int) $this->area_marla,
            (int) $this->area_sqft,
        );
    }

    public function isAvailable(): bool
    {
        return $this->status === self::STATUS_AVAILABLE;
    }
}
