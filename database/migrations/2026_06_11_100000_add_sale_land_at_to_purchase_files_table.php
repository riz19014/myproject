<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_files', function (Blueprint $table) {
            $table->timestamp('sale_land_at')->nullable()->after('file_date');
        });
    }

    public function down(): void
    {
        Schema::table('purchase_files', function (Blueprint $table) {
            $table->dropColumn('sale_land_at');
        });
    }
};
