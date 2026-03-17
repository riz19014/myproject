<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Factory extends Model
{
    protected $table = 'factories';

    protected $fillable = ['name', 'purchase_cost', 'purchase_date', 'location', 'notes'];

    protected function casts(): array
    {
        return [
            'purchase_date' => 'date',
            'purchase_cost' => 'decimal:2',
        ];
    }

    public function dayBookEntries(): HasMany
    {
        return $this->hasMany(DayBookEntry::class, 'link_id')->where('link_type', 'factory');
    }
}
