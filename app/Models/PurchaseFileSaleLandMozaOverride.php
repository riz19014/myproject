<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseFileSaleLandMozaOverride extends Model
{
    protected $fillable = [
        'purchase_file_id',
        'moza_key',
        'land_provider',
        'land_owner',
        'transfer_to',
    ];

    public function purchaseFile(): BelongsTo
    {
        return $this->belongsTo(PurchaseFile::class);
    }
}
