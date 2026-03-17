<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlotDocument extends Model
{
    protected $fillable = ['plot_id', 'name', 'file_path'];

    public function plot(): BelongsTo
    {
        return $this->belongsTo(Plot::class);
    }
}
