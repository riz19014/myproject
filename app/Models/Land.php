<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Land extends Model
{
    protected $fillable = ['name', 'total_area_kanal', 'location', 'purchase_date', 'notes'];

    protected function casts(): array
    {
        return ['purchase_date' => 'date'];
    }

    public function plots(): HasMany
    {
        return $this->hasMany(Plot::class);
    }

    public function dayBookEntries(): HasMany
    {
        return $this->hasMany(DayBookEntry::class, 'link_id')->where('link_type', 'land');
    }
}
