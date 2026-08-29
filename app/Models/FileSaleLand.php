<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FileSaleLand extends Model
{
    protected $table = 'file_sale_land';

    protected $fillable = [
        'project_id',
        'sale_land_id',
        'collective_id',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function saleLand(): BelongsTo
    {
        return $this->belongsTo(PurchaseFile::class, 'sale_land_id');
    }

    public function collective(): BelongsTo
    {
        return $this->belongsTo(FileSaleCollective::class, 'collective_id');
    }
}
