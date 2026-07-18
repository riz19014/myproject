<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Jobs\ProcessPurchaseFileDocumentJob;
use App\Support\LandMeasure;
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

    public function fileSaleLand(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(FileSaleLand::class, 'sale_land_id');
    }

    public function isMovedToFileSale(): bool
    {
        return $this->relationLoaded('fileSaleLand')
            ? $this->fileSaleLand !== null
            : $this->fileSaleLand()->exists();
    }

    public function dayBookEntries(): HasMany
    {
        return $this->hasMany(DayBookEntry::class);
    }

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }

    /** Total land area on this file from purchase items (canonical marla). */
    public function totalLandMarla(): float
    {
        if ($this->relationLoaded('purchaseItems')) {
            return round((float) $this->purchaseItems->sum('land_area_marla'), 6);
        }

        return round((float) $this->purchaseItems()->sum('land_area_marla'), 6);
    }

    /**
     * Area sold via Day Book file-sale entries (canonical marla).
     */
    public function daybookSoldMarla(?int $excludeEntryId = null): float
    {
        $query = DayBookEntry::query()
            ->where('purchase_file_id', $this->id)
            ->whereNotNull('sold_area_marla')
            ->where('sold_area_marla', '>', 0);

        if ($excludeEntryId !== null) {
            $query->where('id', '!=', $excludeEntryId);
        }

        return round((float) $query->sum('sold_area_marla'), 6);
    }

    /**
     * Area sold via Sale records linked to this purchase file (canonical marla).
     */
    public function recordedSaleSoldMarla(): float
    {
        if ($this->relationLoaded('sales')) {
            return round((float) $this->sales->sum('land_area_marla'), 6);
        }

        return round((float) $this->sales()->sum('land_area_marla'), 6);
    }

    /**
     * Total sold area (daybook + sales table).
     */
    public function soldLandMarla(?int $excludeDaybookEntryId = null): float
    {
        return round($this->daybookSoldMarla($excludeDaybookEntryId) + $this->recordedSaleSoldMarla(), 6);
    }

    public function remainingLandMarla(?int $excludeDaybookEntryId = null): float
    {
        return max(0.0, round($this->totalLandMarla() - $this->soldLandMarla($excludeDaybookEntryId), 6));
    }

    public function isFullySold(?int $excludeDaybookEntryId = null): bool
    {
        $total = $this->totalLandMarla();
        if ($total <= 1e-6) {
            return false;
        }

        return $this->remainingLandMarla($excludeDaybookEntryId) <= 1e-6;
    }

    public function saleStatusLabel(?int $excludeDaybookEntryId = null): string
    {
        $total = $this->totalLandMarla();
        if ($total <= 1e-6) {
            return 'No area';
        }
        $remaining = $this->remainingLandMarla($excludeDaybookEntryId);
        if ($remaining <= 1e-6) {
            return 'Fully Sold';
        }
        if ($this->soldLandMarla($excludeDaybookEntryId) > 1e-6) {
            return 'Partially Sold';
        }

        return 'Available';
    }

    /**
     * Payload for daybook sale-file select / balance UI.
     *
     * @return array<string, mixed>
     */
    public function daybookSaleFilePayload(?int $excludeDaybookEntryId = null): array
    {
        $total = $this->totalLandMarla();
        $sold = $this->soldLandMarla($excludeDaybookEntryId);
        $remaining = $this->remainingLandMarla($excludeDaybookEntryId);
        $fullySold = $remaining <= 1e-6 && $total > 1e-6;

        $sellers = [];
        if ($this->relationLoaded('purchaseItems')) {
            $sellers = $this->purchaseItems
                ->map(fn ($item) => $item->party?->name)
                ->filter()
                ->unique()
                ->values()
                ->all();
        }

        return [
            'id' => $this->id,
            'label' => $this->file_name,
            'file_name' => $this->file_name,
            'project_id' => $this->project_id,
            'is_file_sale' => $this->isMovedToFileSale(),
            'total_marla' => $total,
            'sold_marla' => $sold,
            'remaining_marla' => $remaining,
            'total_label' => $total > 0 ? LandMeasure::formatAkmsLabelFromMarla($total) : '—',
            'sold_label' => $sold > 0 ? LandMeasure::formatAkmsLabelFromMarla($sold) : '—',
            'remaining_label' => $total > 0 ? LandMeasure::formatAkmsLabelFromMarla($remaining) : '—',
            'unit' => 'marla',
            'status' => $this->saleStatusLabel($excludeDaybookEntryId),
            'is_fully_sold' => $fullySold,
            'sellers' => $sellers,
            'customer_hint' => $sellers !== [] ? implode(', ', $sellers) : null,
        ];
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
