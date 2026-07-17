<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('day_book_entries', function (Blueprint $table) {
            if (! Schema::hasColumn('day_book_entries', 'sold_area_marla')) {
                $table->decimal('sold_area_marla', 14, 6)->nullable()->after('purchase_file_id');
            }
            if (! Schema::hasColumn('day_book_entries', 'sold_area_qty')) {
                $table->decimal('sold_area_qty', 14, 4)->nullable()->after('sold_area_marla');
            }
            if (! Schema::hasColumn('day_book_entries', 'sold_area_unit')) {
                $table->string('sold_area_unit', 20)->nullable()->after('sold_area_qty');
            }
        });
    }

    public function down(): void
    {
        Schema::table('day_book_entries', function (Blueprint $table) {
            if (Schema::hasColumn('day_book_entries', 'sold_area_unit')) {
                $table->dropColumn('sold_area_unit');
            }
            if (Schema::hasColumn('day_book_entries', 'sold_area_qty')) {
                $table->dropColumn('sold_area_qty');
            }
            if (Schema::hasColumn('day_book_entries', 'sold_area_marla')) {
                $table->dropColumn('sold_area_marla');
            }
        });
    }
};
