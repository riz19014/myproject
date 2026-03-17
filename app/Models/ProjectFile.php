<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\UploadedFile;

class ProjectFile extends Model
{
    protected $fillable = ['project_id', 'file_number', 'status', 'sale_amount', 'customer_id', 'sale_date', 'notes'];

    protected function casts(): array
    {
        return [
            'sale_date' => 'date',
            'sale_amount' => 'decimal:2',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(ProjectFileDocument::class);
    }

    public function addDocument(UploadedFile $file): ProjectFileDocument
    {
        $path = $file->store('project-files/' . $this->id, 'public');
        return $this->documents()->create(['name' => $file->getClientOriginalName(), 'file_path' => $path]);
    }
}
