<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FileSaleCollective extends Model
{
    public const STATUS_OPEN = 'open';

    public const STATUS_COMPLETED = 'completed';

    protected $fillable = [
        'project_id',
        'name',
        'status',
        'exemption_payload',
        'total_land_marla',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'exemption_payload' => 'array',
            'total_land_marla' => 'decimal:4',
            'completed_at' => 'datetime',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function fileSaleLands(): HasMany
    {
        return $this->hasMany(FileSaleLand::class, 'collective_id');
    }

    public function isOpen(): bool
    {
        return $this->status === self::STATUS_OPEN;
    }

    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function exemptionSummaryLabel(): string
    {
        $components = $this->exemption_payload['components'] ?? [];
        if (! is_array($components) || $components === []) {
            return '—';
        }

        $parts = [];
        foreach ($components as $component) {
            $label = trim((string) ($component['label'] ?? $component['slug'] ?? ''));
            $pct = rtrim(rtrim(number_format((float) ($component['pool_percent'] ?? 0), 4, '.', ''), '0'), '.');
            $parts[] = $label.' '.$pct.'%';
        }

        return $parts !== [] ? implode(' · ', $parts) : '—';
    }
}
