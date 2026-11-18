<?php

namespace App\Models;

use App\Support\LandMeasure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectLandAllocation extends Model
{
    public const CATEGORIES = [
        'residential' => 'Residential plots',
        'commercial' => 'Commercial plots',
        'road' => 'Roads',
        'park' => 'Parks',
        'amenity' => 'Amenities',
        'wastage' => 'Wastage',
        'unallocated' => 'Unallocated',
    ];

    /** Master-plan categories that are not sold as plots/files. */
    public const NON_SALEABLE_CATEGORIES = [
        'road',
        'park',
        'amenity',
        'wastage',
        'unallocated',
    ];

    public function isSaleable(): bool
    {
        return ! in_array((string) $this->category, self::NON_SALEABLE_CATEGORIES, true);
    }

    protected $fillable = [
        'project_id',
        'category',
        'name',
        'area_acre',
        'area_kanal',
        'area_marla',
        'area_sqft',
        'land_area_marla',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'land_area_marla' => 'decimal:6',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function categoryLabel(): string
    {
        $custom = trim((string) ($this->name ?? ''));
        if ($custom !== '') {
            return $custom;
        }

        return self::CATEGORIES[$this->category] ?? $this->category;
    }

    public static function defaultLabelFor(string $category): string
    {
        return self::CATEGORIES[$category] ?? $category;
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
}
