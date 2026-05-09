<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LandCutting extends Model
{
    public const TYPES = [
        'road' => 'Road',
        'park' => 'Park',
        'graveyard' => 'Graveyard',
        'masjid' => 'Masjid',
        'green_belt' => 'Green Belt',
        'commercial_reserve' => 'Commercial Reserve',
        'utility_area' => 'Utility Area',
    ];

    protected $fillable = [
        'sale_id',
        'project_id',
        'cutting_type',
        'area_acre',
        'area_kanal',
        'area_marla',
        'area_sqft',
        'land_area_marla',
    ];

    protected function casts(): array
    {
        return [
            'land_area_marla' => 'decimal:4',
        ];
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function typeLabel(): string
    {
        return self::TYPES[$this->cutting_type] ?? $this->cutting_type;
    }
}
