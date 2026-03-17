<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Job extends Model
{
    protected $table = 'job_listings';

    protected $fillable = [
        'title',
        'description',
        'no_of_positions',
        'salary_range',
        'location',
        'job_type',
    ];

    protected function casts(): array
    {
        return [
            'no_of_positions' => 'integer',
        ];
    }

    public const LOCATIONS = ['onsite', 'remote'];

    public const JOB_TYPES = ['permanent', 'contract', 'part_time', 'full_time'];
}
