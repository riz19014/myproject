<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class PlotBookingDocument extends Model
{
    protected $fillable = [
        'plot_booking_id',
        'name',
        'file_path',
    ];

    protected static function booted(): void
    {
        static::deleting(function (PlotBookingDocument $doc) {
            if ($doc->file_path) {
                Storage::disk('public')->delete($doc->file_path);
            }
        });
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(PlotBooking::class, 'plot_booking_id');
    }

    public function publicUrl(): string
    {
        return Storage::disk('public')->url($this->file_path);
    }
}
