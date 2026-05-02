<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('day_book_entries', function (Blueprint $table) {
            $table->string('payment_method', 20)->nullable()->after('type');
            $table->string('payment_bank', 120)->nullable()->after('payment_method');
            $table->string('payment_reference', 100)->nullable()->after('payment_bank');
        });
    }

    public function down(): void
    {
        Schema::table('day_book_entries', function (Blueprint $table) {
            $table->dropColumn(['payment_method', 'payment_bank', 'payment_reference']);
        });
    }
};
