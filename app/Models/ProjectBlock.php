<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProjectBlock extends Model
{
    protected $fillable = [
        'project_id',
        'name',
        'sector',
        'category',
        'notes',
        'sort_order',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function plots(): HasMany
    {
        return $this->hasMany(ProjectPlot::class)->orderBy('plot_number');
    }
}
