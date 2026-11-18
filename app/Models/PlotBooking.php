<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Collection;

class PlotBooking extends Model
{
    public const STATUS_BOOKED = 'booked';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUS_TRANSFERRED = 'transferred';

    /** Max leftover (Rs) that can be waived as rounding on the payment sheet. */
    public const IGNORE_OUTSTANDING_MAX = 1.0;

    public const STATUSES = [
        self::STATUS_BOOKED => 'Booked',
        self::STATUS_ACTIVE => 'Active',
        self::STATUS_COMPLETED => 'Completed',
        self::STATUS_CANCELLED => 'Cancelled',
        self::STATUS_TRANSFERRED => 'Transferred',
    ];

    protected $fillable = [
        'project_id',
        'project_plot_id',
        'sale_id',
        'customer_id',
        'party_id',
        'status',
        'booking_date',
        'total_price',
        'booking_amount',
        'booking_day_book_entry_id',
        'transferred_from_booking_id',
        'notes',
        'cancelled_at',
        'cancel_reason',
    ];

    protected function casts(): array
    {
        return [
            'booking_date' => 'date',
            'total_price' => 'decimal:2',
            'booking_amount' => 'decimal:2',
            'cancelled_at' => 'datetime',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function plot(): BelongsTo
    {
        return $this->belongsTo(ProjectPlot::class, 'project_plot_id');
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function party(): BelongsTo
    {
        return $this->belongsTo(Party::class);
    }

    public function installmentPlan(): HasOne
    {
        return $this->hasOne(InstallmentPlan::class);
    }

    public function bookingEntry(): BelongsTo
    {
        return $this->belongsTo(DayBookEntry::class, 'booking_day_book_entry_id');
    }

    public function dayBookEntries(): HasMany
    {
        return $this->hasMany(DayBookEntry::class, 'plot_booking_id');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(PlotBookingDocument::class);
    }

    public function addDocument(\Illuminate\Http\UploadedFile $file): PlotBookingDocument
    {
        $path = $file->store('society-sales/'.$this->id, 'public');

        return $this->documents()->create([
            'name' => $file->getClientOriginalName(),
            'file_path' => $path,
        ]);
    }

    public function transferredFrom(): BelongsTo
    {
        return $this->belongsTo(self::class, 'transferred_from_booking_id');
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function paidTotal(): float
    {
        $planPaid = (float) ($this->installmentPlan?->dues->sum('paid_amount') ?? 0);

        return (float) $this->booking_amount + $planPaid;
    }

    public function outstanding(): float
    {
        return max(0, (float) $this->total_price - $this->paidTotal());
    }

    /**
     * Daybook Payment-in lines counted against this sales file.
     *
     * @return Collection<int, DayBookEntry>
     */
    public function fileDaybookReceipts(): Collection
    {
        if ($this->relationLoaded('dayBookEntries')) {
            $linked = $this->dayBookEntries
                ->where('type', DayBookEntry::TYPE_CASH_IN)
                ->sortBy([
                    ['entry_date', 'asc'],
                    ['id', 'asc'],
                ])
                ->values();
            if ($linked->isNotEmpty()) {
                return $linked;
            }
        } else {
            $linked = DayBookEntry::query()
                ->where('plot_booking_id', $this->id)
                ->where('type', DayBookEntry::TYPE_CASH_IN)
                ->orderBy('entry_date')
                ->orderBy('id')
                ->get();
            if ($linked->isNotEmpty()) {
                return $linked;
            }
        }

        // Fallback for entries saved before plot_booking_id was linked:
        // project + party Payment in (include land-sale descriptions / file #).
        if (! $this->party_id || ! $this->project_id) {
            return collect();
        }

        $plotNumber = $this->plot?->plot_number;
        $rows = DayBookEntry::query()
            ->where('project_id', $this->project_id)
            ->where('type', DayBookEntry::TYPE_CASH_IN)
            ->where('link_type', DayBookEntry::LINK_PARTY)
            ->where('link_id', $this->party_id)
            ->where(function ($q) {
                $q->whereNull('plot_booking_id')
                    ->orWhere('plot_booking_id', $this->id);
            })
            ->orderBy('entry_date')
            ->orderBy('id')
            ->get();

        if ($rows->isEmpty()) {
            return collect();
        }

        $needle = $plotNumber ? mb_strtolower((string) $plotNumber) : '';
        $matched = $rows->filter(function (DayBookEntry $e) use ($needle) {
            $desc = mb_strtolower((string) $e->description);
            if ($needle !== '' && str_contains($desc, $needle)) {
                return true;
            }

            // Daybook land-sale Payment in (e.g. "Land sale — … · A 0 — K 2 — M 5 …")
            return str_contains($desc, 'land sale');
        });

        if ($matched->isNotEmpty()) {
            return $matched->values();
        }

        // If this party has only this active file on the project, use all their project receipts.
        $otherActive = static::query()
            ->where('project_id', $this->project_id)
            ->where('party_id', $this->party_id)
            ->where('id', '!=', $this->id)
            ->whereNotIn('status', [self::STATUS_CANCELLED, self::STATUS_TRANSFERRED])
            ->exists();

        return $otherActive ? collect() : $rows->values();
    }

    /**
     * Amount received against this file (daybook Payment in, else booking/installment paid).
     */
    public function fileReceivedAmount(): float
    {
        $fromDaybook = (float) $this->fileDaybookReceipts()->sum('amount');
        if ($fromDaybook > 0.009) {
            return round($fromDaybook, 2);
        }

        return round($this->paidTotal(), 2);
    }

    public function fileOutstandingAmount(): float
    {
        return max(0, round((float) $this->total_price - $this->fileReceivedAmount(), 2));
    }

    public function canIgnoreOutstandingRounding(?float $outstanding = null): bool
    {
        if (in_array($this->status, [self::STATUS_COMPLETED, self::STATUS_CANCELLED, self::STATUS_TRANSFERRED], true)) {
            return false;
        }

        $outstanding ??= $this->fileOutstandingAmount();

        return $outstanding > 0.009 && $outstanding <= self::IGNORE_OUTSTANDING_MAX;
    }

    /**
     * Daybook Payment-in is the source of truth for received cash.
     * Keep booking_amount in sync and shrink the installment schedule to the unpaid remainder.
     */
    public function syncFromDaybookReceipts(): void
    {
        $received = (float) DayBookEntry::query()
            ->where('plot_booking_id', $this->id)
            ->where('type', DayBookEntry::TYPE_CASH_IN)
            ->sum('amount');

        $received = min((float) $this->total_price, round($received, 2));
        $remaining = max(0, round((float) $this->total_price - $received, 2));

        $updates = ['booking_amount' => $received];
        if ($remaining <= 0.009) {
            $updates['status'] = self::STATUS_COMPLETED;
            if ($this->plot) {
                $this->plot->update(['status' => ProjectPlot::STATUS_SOLD]);
            }
        } elseif (in_array($this->status, [self::STATUS_COMPLETED], true)) {
            $updates['status'] = self::STATUS_ACTIVE;
            if ($this->plot && $this->plot->status === ProjectPlot::STATUS_SOLD) {
                $this->plot->update(['status' => ProjectPlot::STATUS_BOOKED]);
            }
        }

        $this->update($updates);
        $this->syncInstallmentScheduleRemaining($remaining);
    }

    /**
     * Installment dues only hold what is still unpaid after daybook receipts.
     */
    public function syncInstallmentScheduleRemaining(float $remaining): void
    {
        $this->loadMissing('installmentPlan.dues');
        $plan = $this->installmentPlan;
        $remaining = max(0, round($remaining, 2));

        if (! $plan) {
            if ($remaining <= 0.009) {
                return;
            }

            $plan = InstallmentPlan::query()->create([
                'plot_booking_id' => $this->id,
                'name' => 'Payment schedule',
                'installment_count' => 1,
                'total_amount' => $remaining,
            ]);

            InstallmentDue::query()->create([
                'installment_plan_id' => $plan->id,
                'sequence' => 1,
                'label' => 'Balance due',
                'due_date' => now()->addMonth()->toDateString(),
                'amount' => $remaining,
                'paid_amount' => 0,
                'status' => InstallmentDue::STATUS_PENDING,
            ]);

            $this->unsetRelation('installmentPlan');

            return;
        }

        $dues = $plan->dues->sortBy('sequence')->values();
        $count = max(1, $dues->count());

        $plan->update([
            'total_amount' => $remaining,
            'installment_count' => $count,
        ]);

        if ($remaining <= 0.009) {
            foreach ($dues as $due) {
                $due->amount = 0;
                $due->paid_amount = 0;
                $due->status = InstallmentDue::STATUS_PAID;
                $due->paid_at = $due->paid_at ?? now();
                $due->save();
            }

            return;
        }

        $base = floor(($remaining / $count) * 100) / 100;
        $allocated = 0.0;
        foreach ($dues as $i => $due) {
            $amount = $i === $count - 1
                ? round($remaining - $allocated, 2)
                : $base;
            $allocated += $amount;
            $due->amount = $amount;
            // Daybook receipts are tracked on booking_amount, not installment paid_amount.
            $due->paid_amount = 0;
            $due->paid_at = null;
            $due->refreshStatus();
            $due->save();
        }
    }
}
