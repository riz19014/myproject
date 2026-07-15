<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('day_book_entries', function (Blueprint $table) {
            if (! Schema::hasColumn('day_book_entries', 'sub_category_id')) {
                $table->unsignedBigInteger('sub_category_id')->nullable()->after('party_sub_category_id');
            }
            if (! Schema::hasColumn('day_book_entries', 'unit')) {
                $table->string('unit', 50)->nullable()->after('sub_category_id');
            }
            if (! Schema::hasColumn('day_book_entries', 'quantity')) {
                $table->unsignedInteger('quantity')->nullable()->after('unit');
            }
            if (! Schema::hasColumn('day_book_entries', 'unit_price')) {
                $table->decimal('unit_price', 15, 2)->nullable()->after('quantity');
            }
        });
    }

    public function down(): void
    {
        Schema::table('day_book_entries', function (Blueprint $table) {
            if (Schema::hasColumn('day_book_entries', 'unit_price')) {
                $table->dropColumn('unit_price');
            }
            if (Schema::hasColumn('day_book_entries', 'quantity')) {
                $table->dropColumn('quantity');
            }
            if (Schema::hasColumn('day_book_entries', 'unit')) {
                $table->dropColumn('unit');
            }
            if (Schema::hasColumn('day_book_entries', 'sub_category_id')) {
                $table->dropColumn('sub_category_id');
            }
        });
    }
};

