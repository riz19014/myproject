<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('day_book_entries', function (Blueprint $table) {
            $table->foreignId('project_id')->nullable()->after('link_id')->constrained()->nullOnDelete();
        });

        DB::table('day_book_entries')
            ->where('link_type', 'project')
            ->whereNotNull('link_id')
            ->update(['project_id' => DB::raw('link_id')]);
    }

    public function down(): void
    {
        Schema::table('day_book_entries', function (Blueprint $table) {
            $table->dropForeign(['project_id']);
            $table->dropColumn('project_id');
        });
    }
};
