<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectSaleExemptionPlotType extends Model
{
    protected $fillable = [
        'project_id',
        'component_id',
        'slug',
        'label',
        'marla_per_plot',
        'nominal_marla',
        'share_percent',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'marla_per_plot' => 'decimal:4',
            'nominal_marla' => 'decimal:4',
            'share_percent' => 'decimal:4',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function component(): BelongsTo
    {
        return $this->belongsTo(ProjectSaleExemptionComponent::class, 'component_id');
    }
}
