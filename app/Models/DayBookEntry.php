<?php

namespace App\Models;

use App\Support\LandMeasure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DayBookEntry extends Model
{
    protected $fillable = [
        'entry_date',
        'voucher_no',
        'type',
        'amount',
        // Factory-only expense fields (nullable; only for Factory projects)
        'sub_category_id',
        'unit',
        'quantity',
        'unit_price',
        'description',
        'payment_method',
        'payment_bank',
        'payment_reference',
        'paid_by_party_id',
        'link_type',
        'link_id',
        'project_id',
        'purchase_file_id',
        'sold_area_marla',
        'sold_area_qty',
        'sold_area_unit',
        'party_sub_category_id',
    ];

    protected function casts(): array
    {
        return [
            'entry_date' => 'date',
            'amount' => 'decimal:2',
            'unit_price' => 'decimal:2',
            'sold_area_marla' => 'decimal:6',
            'sold_area_qty' => 'decimal:4',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function purchaseFile(): BelongsTo
    {
        return $this->belongsTo(PurchaseFile::class);
    }

    public function partySubCategory(): BelongsTo
    {
        return $this->belongsTo(PartySubCategory::class);
    }

    public function paidByParty(): BelongsTo
    {
        return $this->belongsTo(Party::class, 'paid_by_party_id');
    }

    public function subCategory(): BelongsTo
    {
        return $this->belongsTo(PartySubCategory::class, 'sub_category_id');
    }

    /**
     * True when this entry carries Factory expense details (Construction & Material).
     */
    public function isFactoryExpense(): bool
    {
        return $this->sub_category_id !== null
            || $this->quantity !== null
            || $this->unit_price !== null;
    }

    public function getFactorySubCategoryLabel(): string
    {
        $sc = $this->subCategory;
        if (! $sc) {
            return '—';
        }
        $cat = $sc->category?->name ?? '—';

        return $cat.' — '.$sc->name;
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

    /**
     * Daybook lines tied to this project (direct project_id or link_type + link_id project).
     */
    public function scopeLinkedToProject(Builder $query, Project $project): Builder
    {
        return $query->where(function ($q) use ($project) {
            $q->where('project_id', $project->id)
                ->orWhere(function ($q2) use ($project) {
                    $q2->where('link_type', self::LINK_PROJECT)
                        ->where('link_id', $project->id);
                });
        });
    }

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
            return $this->appendPurchaseFileToLabel('Party: '.($m->name ?? ('#'.$this->link_id)));
        }

        return $this->appendPurchaseFileToLabel($m->name ?? ('#'.$this->link_id));
    }

    public function getPurchaseFileLabel(): string
    {
        return $this->purchaseFile?->file_name ?? '—';
    }

    private function appendPurchaseFileToLabel(string $label): string
    {
        $fileName = $this->purchaseFile?->file_name;
        if ($fileName) {
            return $label.' · File: '.$fileName;
        }

        return $label;
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

    public function getVoucherNumber(): string
    {
        return \App\Support\DaybookVoucher::display($this->voucher_no);
    }

    public function getSettlementLabel(): string
    {
        $method = $this->payment_method;
        if ($method === null || $method === '') {
            return '—';
        }

        return match ($method) {
            self::PAYMENT_CASH => 'Cash',
            self::PAYMENT_ONLINE => 'Online'
                .($this->payment_bank ? ' · Bank: '.$this->payment_bank : ''),
            self::PAYMENT_CHEQUE => 'Cheque'
                .($this->payment_bank ? ' · Bank: '.$this->payment_bank : '')
                .($this->payment_reference ? ' · No. '.$this->payment_reference : ''),
            self::PAYMENT_PAYORDER => 'Pay order'
                .($this->payment_bank ? ' · Bank: '.$this->payment_bank : '')
                .($this->payment_reference ? ' · No. '.$this->payment_reference : ''),
            default => (string) $method,
        };
    }

    public function getPaidByLabel(): string
    {
        return $this->paidByParty?->name ?? '—';
    }

    public function hasFileSaleArea(): bool
    {
        return $this->sold_area_marla !== null && (float) $this->sold_area_marla > 1e-6;
    }

    public function getSoldAreaLabel(): string
    {
        if (! $this->hasFileSaleArea()) {
            return '—';
        }

        $qty = $this->sold_area_qty !== null ? (float) $this->sold_area_qty : null;
        $unit = trim((string) $this->sold_area_unit);
        if ($qty !== null && $qty > 0 && $unit !== '' && in_array($unit, ['marla', 'kanal', 'acre', 'sqft'], true)) {
            return LandMeasure::formatAmountUnit($qty, $unit)
                .' ('.LandMeasure::formatAkmsLabelFromMarla((float) $this->sold_area_marla).')';
        }

        return LandMeasure::formatAkmsLabelFromMarla((float) $this->sold_area_marla);
    }

    /**
     * Settlement plus optional paid-by party for lists / ledgers / history.
     */
    public function getSettlementWithPaidByLabel(): string
    {
        $settlement = $this->getSettlementLabel();
        $paidBy = $this->getPaidByLabel();
        if ($paidBy === '—') {
            return $settlement;
        }
        if ($settlement === '—') {
            return 'Paid by: '.$paidBy;
        }

        return $settlement.' · Paid by: '.$paidBy;
    }

    /**
     * Compact payment settlement lines for purchase file ledger (PDF / UI).
     *
     * @return list<array{kind: string, text: string}>
     */
    public function ledgerPaymentDetailLines(): array
    {
        $method = $this->payment_method;
        $lines = [];

        $methodLabel = match ($method) {
            self::PAYMENT_CASH => 'Cash',
            self::PAYMENT_ONLINE => 'Online',
            self::PAYMENT_CHEQUE => 'Cheque',
            self::PAYMENT_PAYORDER => 'Pay Order',
            null, '' => $this->type === self::TYPE_CASH_IN ? null : 'Cash',
            default => ucfirst(str_replace('_', ' ', (string) $method)),
        };

        if ($methodLabel !== null && $methodLabel !== '') {
            $lines[] = ['kind' => 'method', 'text' => $methodLabel];
        }

        $paidBy = $this->getPaidByLabel();
        if ($paidBy !== '—') {
            $lines[] = ['kind' => 'meta', 'text' => 'Paid by: '.$paidBy];
        }

        if ($this->hasFileSaleArea()) {
            $lines[] = ['kind' => 'meta', 'text' => 'Sold area: '.$this->getSoldAreaLabel()];
        }

        $bank = trim((string) $this->payment_bank);
        if ($bank !== '' && in_array($method, [self::PAYMENT_CHEQUE, self::PAYMENT_PAYORDER, self::PAYMENT_ONLINE], true)) {
            $lines[] = ['kind' => 'meta', 'text' => $bank];
        }

        $reference = trim((string) $this->payment_reference);
        if ($reference !== '' && in_array($method, [self::PAYMENT_CHEQUE, self::PAYMENT_PAYORDER], true)) {
            $prefix = $method === self::PAYMENT_PAYORDER ? 'PO#' : 'Chq#';
            $lines[] = ['kind' => 'meta', 'text' => $prefix.' '.$reference];
        }

        $description = trim((string) $this->description);
        if ($description !== '') {
            $lines[] = ['kind' => 'desc', 'text' => $description];
        }

        return $lines;
    }
}
