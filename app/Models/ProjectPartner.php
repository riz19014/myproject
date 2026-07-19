<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectPartner extends Model
{
    protected $fillable = [
        'party_id',
        'project_id',
        'purchase_file_id',
        'investment_amount',
        'share_percentage',
    ];

    protected function casts(): array
    {
        return [
            'investment_amount' => 'decimal:2',
            'share_percentage' => 'decimal:2',
        ];
    }

    public function party(): BelongsTo
    {
        return $this->belongsTo(Party::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function purchaseFile(): BelongsTo
    {
        return $this->belongsTo(PurchaseFile::class);
    }
}
