<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\UploadedFile;

class ProjectFile extends Model
{
    protected $fillable = [
        'project_id',
        'dealer_party_id',
        'file_number',
        'area_acre',
        'area_kanal',
        'area_marla',
        'area_sqft',
        'land_area_marla',
        'residential_pool_percent',
        'commercial_pool_percent',
        'status',
        'sale_amount',
        'customer_id',
        'sale_date',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'land_area_marla' => 'decimal:4',
            'residential_pool_percent' => 'decimal:4',
            'commercial_pool_percent' => 'decimal:4',
            'sale_date' => 'date',
            'sale_amount' => 'decimal:2',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function dealerParty(): BelongsTo
    {
        return $this->belongsTo(Party::class, 'dealer_party_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }

    public function exemptionOverrides(): HasMany
    {
        return $this->hasMany(ProjectFileExemptionOverride::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(ProjectFileDocument::class);
    }

    public function directSoldMarla(): float
    {
        $query = $this->sales()->where('sale_type', Sale::TYPE_DIRECT);

        return (float) $query->sum('land_area_marla');
    }

    public function percentageSoldMarla(string $component = null): float
    {
        $query = $this->sales()->where('sale_type', Sale::TYPE_PERCENTAGE);
        if ($component !== null) {
            $query->where('component', $component);
        }

        return (float) $query->sum('land_area_marla');
    }

    /** Total marla sold (direct + percentage) for display. */
    public function soldMarla(): float
    {
        if ($this->relationLoaded('sales')) {
            return (float) $this->sales->sum('land_area_marla');
        }

        return (float) $this->sales()->sum('land_area_marla');
    }

    /** Remaining for direct plot sales only. */
    public function remainingMarla(): float
    {
        $direct = $this->relationLoaded('sales')
            ? (float) $this->sales->where('sale_type', Sale::TYPE_DIRECT)->sum('land_area_marla')
            : $this->directSoldMarla();

        return max(0.0, (float) $this->land_area_marla - $direct);
    }

    public function addDocument(UploadedFile $file): ProjectFileDocument
    {
        $path = $file->store('project-files/' . $this->id, 'public');
        return $this->documents()->create(['name' => $file->getClientOriginalName(), 'file_path' => $path]);
    }
}
