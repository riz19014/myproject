<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectFileExemptionOverride extends Model
{
    protected $fillable = [
        'project_file_id',
        'component_id',
        'pool_percent',
    ];

    protected function casts(): array
    {
        return [
            'pool_percent' => 'decimal:4',
        ];
    }

    public function projectFile(): BelongsTo
    {
        return $this->belongsTo(ProjectFile::class);
    }

    public function component(): BelongsTo
    {
        return $this->belongsTo(ProjectSaleExemptionComponent::class, 'component_id');
    }
}
