<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InstallmentDue extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_PARTIAL = 'partial';

    public const STATUS_PAID = 'paid';

    protected $fillable = [
        'installment_plan_id',
        'sequence',
        'label',
        'due_date',
        'amount',
        'paid_amount',
        'status',
        'day_book_entry_id',
        'paid_at',
    ];

    protected function casts(): array
    {
        return [
            'due_date' => 'date',
            'amount' => 'decimal:2',
            'paid_amount' => 'decimal:2',
            'paid_at' => 'datetime',
        ];
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(InstallmentPlan::class, 'installment_plan_id');
    }

    public function dayBookEntry(): BelongsTo
    {
        return $this->belongsTo(DayBookEntry::class);
    }

    public function outstanding(): float
    {
        return max(0, (float) $this->amount - (float) $this->paid_amount);
    }

    public function refreshStatus(): void
    {
        $paid = (float) $this->paid_amount;
        $amount = (float) $this->amount;
        if ($paid <= 0) {
            $this->status = self::STATUS_PENDING;
        } elseif ($paid + 0.009 >= $amount) {
            $this->status = self::STATUS_PAID;
            $this->paid_at = $this->paid_at ?? now();
        } else {
            $this->status = self::STATUS_PARTIAL;
        }
    }
}
