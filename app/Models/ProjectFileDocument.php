<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectFileDocument extends Model
{
    protected $fillable = ['project_file_id', 'name', 'file_path'];

    public function projectFile(): BelongsTo
    {
        return $this->belongsTo(ProjectFile::class);
    }
}
