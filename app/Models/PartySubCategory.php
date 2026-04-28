<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PartySubCategory extends Model
{
    protected $fillable = ['category_id', 'name'];

    public function category(): BelongsTo
    {
        return $this->belongsTo(PartyCategory::class, 'category_id');
    }

    public function landTypes(): HasMany
    {
        return $this->hasMany(LandType::class);
    }
}
