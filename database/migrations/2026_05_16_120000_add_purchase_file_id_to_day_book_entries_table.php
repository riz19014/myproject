<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('day_book_entries', function (Blueprint $table) {
            $table->foreignId('purchase_file_id')
                ->nullable()
                ->after('project_id')
                ->constrained()
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('day_book_entries', function (Blueprint $table) {
            $table->dropConstrainedForeignId('purchase_file_id');
        });
    }
};
