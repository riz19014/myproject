<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class FileSaleRecordDocument extends Model
{
    protected $fillable = [
        'file_sale_record_id',
        'name',
        'file_path',
    ];

    protected static function booted(): void
    {
        static::deleting(function (FileSaleRecordDocument $doc) {
            if ($doc->file_path) {
                Storage::disk('public')->delete($doc->file_path);
            }
        });
    }

    public function fileSaleRecord(): BelongsTo
    {
        return $this->belongsTo(FileSaleRecord::class);
    }
}
