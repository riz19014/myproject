<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProjectSaleExemptionComponent extends Model
{
    protected $fillable = [
        'project_id',
        'slug',
        'label',
        'pool_percent',
        'marla_per_acre',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'pool_percent' => 'decimal:4',
            'marla_per_acre' => 'decimal:4',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function plotTypes(): HasMany
    {
        return $this->hasMany(ProjectSaleExemptionPlotType::class, 'component_id')->orderBy('sort_order');
    }

    public function fileOverrides(): HasMany
    {
        return $this->hasMany(ProjectFileExemptionOverride::class, 'component_id');
    }

    /** Marla distribution base per acre for this component (e.g. 25% of 160 = 40). */
    public function distributionMarlaPerAcre(): float
    {
        return round((float) $this->pool_percent / 100 * (float) $this->marla_per_acre, 4);
    }
}
