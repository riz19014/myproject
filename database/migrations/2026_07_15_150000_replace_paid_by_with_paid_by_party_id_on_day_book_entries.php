<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('day_book_entries', function (Blueprint $table) {
            if (! Schema::hasColumn('day_book_entries', 'paid_by_party_id')) {
                $table->foreignId('paid_by_party_id')
                    ->nullable()
                    ->after('payment_reference')
                    ->constrained('parties')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('day_book_entries', function (Blueprint $table) {
            if (Schema::hasColumn('day_book_entries', 'paid_by_party_id')) {
                $table->dropConstrainedForeignId('paid_by_party_id');
            }
        });

    }
};
