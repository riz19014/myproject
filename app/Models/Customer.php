<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    protected $fillable = ['name', 'phone', 'email', 'cnic', 'address', 'notes'];

    public function dayBookEntries(): HasMany
    {
        return $this->hasMany(DayBookEntry::class, 'link_id')->where('link_type', 'customer');
    }
}
