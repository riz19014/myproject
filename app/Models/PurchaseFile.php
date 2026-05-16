<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\UploadedFile;

class PurchaseFile extends Model
{
    protected $fillable = [
        'project_id',
        'file_name',
        'file_date',
    ];

    protected function casts(): array
    {
        return [
            'file_date' => 'date',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function dealers(): BelongsToMany
    {
        return $this->belongsToMany(Party::class, 'purchase_file_dealers')
            ->withTimestamps();
    }

    public function purchaseItems(): HasMany
    {
        return $this->hasMany(PurchaseItem::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(PurchaseFileDocument::class);
    }

    public function addDocument(UploadedFile $file): PurchaseFileDocument
    {
        $path = $file->store('purchase-files/'.$this->id, 'public');

        return $this->documents()->create([
            'name' => $file->getClientOriginalName(),
            'file_path' => $path,
        ]);
    }
}
