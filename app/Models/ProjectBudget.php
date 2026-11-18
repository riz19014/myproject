<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectBudget extends Model
{
    protected $fillable = [
        'project_id',
        'category',
        'estimated_amount',
        'party_sub_category_id',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'estimated_amount' => 'decimal:2',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function partySubCategory(): BelongsTo
    {
        return $this->belongsTo(PartySubCategory::class);
    }
}
