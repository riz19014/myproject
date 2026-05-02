<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DayBookEntry extends Model
{
    protected $fillable = [
        'entry_date',
        'type',
        'amount',
        'description',
        'payment_method',
        'payment_bank',
        'payment_reference',
        'link_type',
        'link_id',
        'project_id',
        'party_sub_category_id',
    ];

    protected function casts(): array
    {
        return [
            'entry_date' => 'date',
            'amount' => 'decimal:2',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function partySubCategory(): BelongsTo
    {
        return $this->belongsTo(PartySubCategory::class);
    }

    public const TYPE_CASH_IN = 'cash_in';

    public const TYPE_CASH_OUT = 'cash_out';

    public const PAYMENT_CASH = 'cash';

    public const PAYMENT_ONLINE = 'online';

    public const PAYMENT_CHEQUE = 'cheque';

    public const PAYMENT_PAYORDER = 'payorder';

    public const LINK_OFFICE = 'office';

    public const LINK_PROJECT = 'project';

    public const LINK_LAND = 'land';

    public const LINK_PLOT = 'plot';

    public const LINK_FACTORY = 'factory';

    public const LINK_CUSTOMER = 'customer';

    public const LINK_PARTY = 'party';

    public function getLinkModel(): ?Model
    {
        if (! $this->link_type || ! $this->link_id) {
            return null;
        }

        return match ($this->link_type) {
            'project' => Project::find($this->link_id),
            'land' => Land::find($this->link_id),
            'plot' => Plot::find($this->link_id),
            'factory' => Factory::find($this->link_id),
            'customer' => Customer::find($this->link_id),
            'party' => Party::find($this->link_id),
            default => null,
        };
    }

    public function getLinkLabel(): string
    {
        if ($this->link_type === 'office' || ! $this->link_type) {
            return 'Office';
        }
        $m = $this->getLinkModel();
        if (! $m) {
            return '—';
        }
        if ($m instanceof Plot) {
            return 'Plot: '.$m->plot_number.' ('.$m->land->name.')';
        }
        if ($m instanceof Party) {
            return 'Party: '.($m->name ?? ('#'.$this->link_id));
        }

        return $m->name ?? ('#'.$this->link_id);
    }

    public function getPartySubCategoryLabel(): string
    {
        $sc = $this->partySubCategory;
        if (! $sc) {
            return '—';
        }
        $cat = $sc->category?->name ?? '—';

        return $cat.' — '.$sc->name;
    }

    public function getSettlementLabel(): string
    {
        $method = $this->payment_method;
        if ($method === null || $method === '') {
            return '—';
        }

        return match ($method) {
            self::PAYMENT_CASH => 'Cash',
            self::PAYMENT_ONLINE => 'Online'.($this->payment_bank ? ' · '.$this->payment_bank : ''),
            self::PAYMENT_CHEQUE => 'Cheque'.($this->payment_bank ? ' · '.$this->payment_bank : '')
                .($this->payment_reference ? ' · #'.$this->payment_reference : ''),
            self::PAYMENT_PAYORDER => 'Pay order'.($this->payment_bank ? ' · '.$this->payment_bank : '')
                .($this->payment_reference ? ' · Ref '.$this->payment_reference : ''),
            default => (string) $method,
        };
    }
}
