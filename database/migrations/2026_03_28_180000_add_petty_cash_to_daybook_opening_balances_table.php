<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('daybook_opening_balances', function (Blueprint $table) {
            $table->decimal('petty_cash', 15, 2)->default(0)->after('amount');
        });
    }

    public function down(): void
    {
        Schema::table('daybook_opening_balances', function (Blueprint $table) {
            $table->dropColumn('petty_cash');
        });
    }
};
