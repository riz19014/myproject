<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DaybookOpeningBalance extends Model
{
    protected $table = 'daybook_opening_balances';

    protected $fillable = ['balance_date', 'amount', 'petty_cash'];

    protected function casts(): array
    {
        return [
            'balance_date' => 'date',
            'amount' => 'decimal:2',
            'petty_cash' => 'decimal:2',
        ];
    }
}
