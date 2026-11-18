<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('day_book_entries', function (Blueprint $table) {
            if (! Schema::hasColumn('day_book_entries', 'plot_booking_id')) {
                $table->foreignId('plot_booking_id')
                    ->nullable()
                    ->after('purchase_file_id')
                    ->constrained('plot_bookings')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('day_book_entries', function (Blueprint $table) {
            if (Schema::hasColumn('day_book_entries', 'plot_booking_id')) {
                $table->dropConstrainedForeignId('plot_booking_id');
            }
        });
    }
};
