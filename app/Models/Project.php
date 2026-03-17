<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Project extends Model
{
    protected $fillable = ['name', 'description', 'notes'];

    public function projectFiles(): HasMany
    {
        return $this->hasMany(ProjectFile::class);
    }

    public function dayBookEntries(): HasMany
    {
        return $this->hasMany(DayBookEntry::class, 'link_id')->where('link_type', 'project');
    }
}
