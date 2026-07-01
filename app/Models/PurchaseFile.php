<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Jobs\ProcessPurchaseFileDocumentJob;
use Illuminate\Http\UploadedFile;

class PurchaseFile extends Model
{
    protected $fillable = [
        'project_id',
        'file_name',
        'file_date',
        'sale_land_at',
    ];

    protected function casts(): array
    {
        return [
            'file_date' => 'date',
            'sale_land_at' => 'datetime',
        ];
    }

    public function isSaleLand(): bool
    {
        return $this->sale_land_at !== null;
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function dealers(): BelongsToMany
    {
        return $this->belongsToMany(Party::class, 'purchase_file_dealers')
            ->withPivot('commission_rs')
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

    public function saleLandMozaOverrides(): HasMany
    {
        return $this->hasMany(PurchaseFileSaleLandMozaOverride::class);
    }

    public function addDocument(UploadedFile $file): PurchaseFileDocument
    {
        $path = $file->store('purchase-files/'.$this->id, 'public');

        return $this->documents()->create([
            'name' => $file->getClientOriginalName(),
            'file_path' => $path,
            'status' => PurchaseFileDocument::STATUS_COMPLETED,
        ]);
    }

    public function queueDocument(UploadedFile $file): PurchaseFileDocument
    {
        $tempPath = $file->store('purchase-files-temp/'.$this->id, 'local');

        $document = $this->documents()->create([
            'name' => $file->getClientOriginalName(),
            'file_path' => $tempPath,
            'status' => PurchaseFileDocument::STATUS_PENDING,
        ]);

        ProcessPurchaseFileDocumentJob::dispatch($document);

        return $document;
    }
}
