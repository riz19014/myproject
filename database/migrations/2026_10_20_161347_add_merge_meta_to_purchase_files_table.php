<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_files', function (Blueprint $table) {
            $table->json('merge_meta')->nullable()->after('sale_land_at');
        });
    }

    public function down(): void
    {
        Schema::table('purchase_files', function (Blueprint $table) {
            $table->dropColumn('merge_meta');
        });
    }
};
