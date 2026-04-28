<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PartyCategory extends Model
{
    protected $fillable = ['name'];

    public function subCategories(): HasMany
    {
        return $this->hasMany(PartySubCategory::class, 'category_id');
    }
}

