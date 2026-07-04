<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Project extends Model
{
    protected $fillable = [
        'name',
        'sort_order',
        'land_type_id',
        'marla_per_acre',
    ];

    protected static function booted(): void
    {
        static::creating(function (Project $project): void {
            if ($project->sort_order !== null) {
                return;
            }

            $max = static::query()->max('sort_order');
            $project->sort_order = $max !== null ? (int) $max + 1 : 1;
        });
    }

    protected function casts(): array
    {
        return [
            'marla_per_acre' => 'decimal:4',
        ];
    }

    public function landType(): BelongsTo
    {
        return $this->belongsTo(LandType::class);
    }

    public function projectFiles(): HasMany
    {
        return $this->hasMany(ProjectFile::class);
    }

    public function dayBookEntries(): HasMany
    {
        return $this->hasMany(DayBookEntry::class, 'project_id');
    }

    public function parties(): BelongsToMany
    {
        return $this->belongsToMany(Party::class)
            ->withPivot(['land_area', 'land_area_unit'])
            ->withTimestamps();
    }

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }

    public function purchaseItems(): HasMany
    {
        return $this->hasMany(PurchaseItem::class);
    }

    public function purchaseFiles(): HasMany
    {
        return $this->hasMany(PurchaseFile::class);
    }

    public function saleExemptionComponents(): HasMany
    {
        return $this->hasMany(ProjectSaleExemptionComponent::class)->orderBy('sort_order');
    }

    public function saleExemptionPlotTypes(): HasMany
    {
        return $this->hasMany(ProjectSaleExemptionPlotType::class);
    }
}
