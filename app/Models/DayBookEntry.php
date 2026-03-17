<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DayBookEntry extends Model
{
    protected $fillable = ['entry_date', 'type', 'amount', 'description', 'link_type', 'link_id'];

    protected function casts(): array
    {
        return [
            'entry_date' => 'date',
            'amount' => 'decimal:2',
        ];
    }

    public const TYPE_CASH_IN = 'cash_in';
    public const TYPE_CASH_OUT = 'cash_out';

    public const LINK_OFFICE = 'office';
    public const LINK_PROJECT = 'project';
    public const LINK_LAND = 'land';
    public const LINK_PLOT = 'plot';
    public const LINK_FACTORY = 'factory';
    public const LINK_CUSTOMER = 'customer';

    public function getLinkModel(): ?Model
    {
        if (!$this->link_type || !$this->link_id) {
            return null;
        }
        return match ($this->link_type) {
            'project' => Project::find($this->link_id),
            'land' => Land::find($this->link_id),
            'plot' => Plot::find($this->link_id),
            'factory' => Factory::find($this->link_id),
            'customer' => Customer::find($this->link_id),
            default => null,
        };
    }

    public function getLinkLabel(): string
    {
        if ($this->link_type === 'office' || !$this->link_type) {
            return 'Office';
        }
        $m = $this->getLinkModel();
        if (!$m) {
            return '—';
        }
        if ($m instanceof Plot) {
            return 'Plot: ' . $m->plot_number . ' (' . $m->land->name . ')';
        }
        return $m->name ?? ('#' . $this->link_id);
    }
}
