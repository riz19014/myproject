<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class PurchaseFileDocument extends Model
{
    protected $fillable = ['purchase_file_id', 'name', 'file_path'];

    protected static function booted(): void
    {
        static::deleting(function (PurchaseFileDocument $doc) {
            if ($doc->file_path) {
                Storage::disk('public')->delete($doc->file_path);
            }
        });
    }

    public function purchaseFile(): BelongsTo
    {
        return $this->belongsTo(PurchaseFile::class);
    }
}
