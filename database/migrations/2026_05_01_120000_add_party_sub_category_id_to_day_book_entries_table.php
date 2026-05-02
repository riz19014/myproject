<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('day_book_entries', function (Blueprint $table) {
            $table->foreignId('party_sub_category_id')
                ->nullable()
                ->after('project_id')
                ->constrained('party_sub_categories')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('day_book_entries', function (Blueprint $table) {
            $table->dropForeign(['party_sub_category_id']);
            $table->dropColumn('party_sub_category_id');
        });
    }
};
