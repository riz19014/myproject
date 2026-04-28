<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LandType extends Model
{
    protected $fillable = ['name', 'party_sub_category_id'];

    public function partySubCategory(): BelongsTo
    {
        return $this->belongsTo(PartySubCategory::class);
    }

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }
}
