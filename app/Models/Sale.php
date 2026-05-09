<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Sale extends Model
{
    protected $table = 'sales';

    protected $fillable = [
        'project_id',
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
            'land_area_marla' => 'decimal:4',
            'total_amount' => 'decimal:2',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
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
