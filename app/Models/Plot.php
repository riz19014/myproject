<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\UploadedFile;

class Plot extends Model
{
    protected $fillable = ['land_id', 'plot_number', 'size', 'status', 'sale_amount', 'customer_id', 'sale_date', 'notes'];

    protected function casts(): array
    {
        return [
            'sale_date' => 'date',
            'sale_amount' => 'decimal:2',
        ];
    }

    public function land(): BelongsTo
    {
        return $this->belongsTo(Land::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(PlotDocument::class);
    }

    public function dayBookEntries(): HasMany
    {
        return $this->hasMany(DayBookEntry::class, 'link_id')->where('link_type', 'plot');
    }

    public function addDocument(UploadedFile $file): PlotDocument
    {
        $path = $file->store('plots/' . $this->id, 'public');
        return $this->documents()->create(['name' => $file->getClientOriginalName(), 'file_path' => $path]);
    }
}
