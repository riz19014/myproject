<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Sale extends Model
{
    protected $table = 'sales';

    public const TYPE_DIRECT = 'direct';

    public const TYPE_PERCENTAGE = 'percentage';

    public const TYPE_SALE_LAND = 'sale_land';

    protected $fillable = [
        'project_id',
        'project_file_id',
        'purchase_file_id',
        'sale_land_moza_keys',
        'sale_type',
        'component',
        'plot_type',
        'plot_quantity',
        'customer_id',
        'area_acre',
        'area_kanal',
        'area_marla',
        'area_sqft',
        'land_area_marla',
        'total_amount',
    ];

    protected function casts(): array
    {
        return [
            'land_area_marla' => 'decimal:12',
            'total_amount' => 'decimal:2',
            'sale_land_moza_keys' => 'array',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function projectFile(): BelongsTo
    {
        return $this->belongsTo(ProjectFile::class);
    }

    public function purchaseFile(): BelongsTo
    {
        return $this->belongsTo(PurchaseFile::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function isDirect(): bool
    {
        return $this->sale_type === self::TYPE_DIRECT;
    }

    public function isPercentage(): bool
    {
        return $this->sale_type === self::TYPE_PERCENTAGE;
    }

    public function isSaleLand(): bool
    {
        return $this->sale_type === self::TYPE_SALE_LAND;
    }

    public function participants(): HasMany
    {
        return $this->hasMany(SaleParticipant::class);
    }

    public function landCuttings(): HasMany
    {
        return $this->hasMany(LandCutting::class);
    }

    public function totalCuttingsMarla(): float
    {
        if ($this->relationLoaded('landCuttings')) {
            return (float) $this->landCuttings->sum('land_area_marla');
        }

        return (float) $this->landCuttings()->sum('land_area_marla');
    }

    public function netSaleableMarla(): float
    {
        return (float) $this->land_area_marla - $this->totalCuttingsMarla();
    }
}
