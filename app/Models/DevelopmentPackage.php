<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DevelopmentPackage extends Model
{
    public const TYPES = [
        'road' => 'Roads',
        'sewerage' => 'Sewerage',
        'water' => 'Water supply',
        'electricity' => 'Electricity',
        'infrastructure' => 'Infrastructure',
        'other' => 'Other',
    ];

    public const STATUSES = [
        'planned' => 'Planned',
        'in_progress' => 'In progress',
        'completed' => 'Completed',
        'on_hold' => 'On hold',
    ];

    protected $fillable = [
        'project_id',
        'name',
        'package_type',
        'contractor_party_id',
        'estimated_amount',
        'progress_percent',
        'status',
        'start_date',
        'target_date',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'estimated_amount' => 'decimal:2',
            'progress_percent' => 'decimal:2',
            'start_date' => 'date',
            'target_date' => 'date',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function contractor(): BelongsTo
    {
        return $this->belongsTo(Party::class, 'contractor_party_id');
    }

    public function boqItems(): HasMany
    {
        return $this->hasMany(BoqItem::class)->orderBy('sort_order')->orderBy('id');
    }

    public function progressLogs(): HasMany
    {
        return $this->hasMany(DevelopmentProgressLog::class)->latest('logged_on');
    }

    public function typeLabel(): string
    {
        return self::TYPES[$this->package_type] ?? $this->package_type;
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function boqEstimatedTotal(): float
    {
        return (float) $this->boqItems->sum('estimated_amount');
    }

    public function boqActualTotal(): float
    {
        return (float) $this->boqItems->sum('actual_amount');
    }
}
