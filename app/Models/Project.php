<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Project extends Model
{
    protected $fillable = [
        'name',
        'land_area',
        'land_area_unit',
        'field_type',
        'land_type_id',
        'total_amount',
        'description',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'land_area' => 'decimal:4',
            'total_amount' => 'decimal:2',
        ];
    }

    public function landType(): BelongsTo
    {
        return $this->belongsTo(LandType::class);
    }

    public function projectFiles(): HasMany
    {
        return $this->hasMany(ProjectFile::class);
    }

    public function dayBookEntries(): HasMany
    {
        return $this->hasMany(DayBookEntry::class, 'project_id');
    }

    public function parties(): BelongsToMany
    {
        return $this->belongsToMany(Party::class)->withTimestamps();
    }
}
