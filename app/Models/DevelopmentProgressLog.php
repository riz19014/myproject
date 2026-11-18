<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DevelopmentProgressLog extends Model
{
    protected $fillable = [
        'development_package_id',
        'user_id',
        'progress_percent',
        'notes',
        'logged_on',
    ];

    protected function casts(): array
    {
        return [
            'progress_percent' => 'decimal:2',
            'logged_on' => 'date',
        ];
    }

    public function package(): BelongsTo
    {
        return $this->belongsTo(DevelopmentPackage::class, 'development_package_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
