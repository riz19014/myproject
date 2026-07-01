<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class PurchaseFileDocument extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_PROCESSING = 'processing';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_FAILED = 'failed';

    protected $fillable = ['purchase_file_id', 'name', 'file_path', 'status', 'error_message'];

    protected static function booted(): void
    {
        static::deleting(function (PurchaseFileDocument $doc) {
            if (! $doc->file_path) {
                return;
            }

            Storage::disk($doc->storageDisk())->delete($doc->file_path);
        });
    }

    public function isProcessed(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function isProcessing(): bool
    {
        return in_array($this->status, [self::STATUS_PENDING, self::STATUS_PROCESSING], true);
    }

    public function storageDisk(): string
    {
        return $this->isProcessed() ? 'public' : 'local';
    }

    public function purchaseFile(): BelongsTo
    {
        return $this->belongsTo(PurchaseFile::class);
    }
}
