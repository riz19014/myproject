<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\UploadedFile;

class FileSaleRecord extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_COMPLETE = 'complete';

    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'project_id',
        'purchase_file_id',
        'sale_id',
        'day_book_entry_id',
        'e_stamp_id',
        'land_owner',
        'land_provider',
        'purchaser_name',
        'moza',
        'khasra',
        'khewat_no',
        'khatooni_no',
        'component',
        'plot_type',
        'plot_quantity',
        'land_area_marla',
        'total_amount',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'land_area_marla' => 'decimal:4',
            'total_amount' => 'decimal:2',
            'plot_quantity' => 'integer',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function purchaseFile(): BelongsTo
    {
        return $this->belongsTo(PurchaseFile::class);
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function dayBookEntry(): BelongsTo
    {
        return $this->belongsTo(DayBookEntry::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(FileSaleRecordDocument::class);
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => 'Pending',
            self::STATUS_COMPLETE => 'Complete',
            self::STATUS_CANCELLED => 'Cancelled',
            default => ucfirst((string) $this->status),
        };
    }

    public function isActiveInventory(): bool
    {
        return in_array($this->status, [self::STATUS_PENDING, self::STATUS_COMPLETE], true);
    }

    public function addDocument(UploadedFile $file): FileSaleRecordDocument
    {
        $path = $file->store('file-sale-records/'.$this->id, 'public');

        return $this->documents()->create([
            'name' => $file->getClientOriginalName(),
            'file_path' => $path,
        ]);
    }
}
