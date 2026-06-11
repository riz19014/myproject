<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_file_sale_land_moza_overrides', function (Blueprint $table) {
            $table->string('land_provider', 255)->nullable()->after('moza_key');
        });
    }

    public function down(): void
    {
        Schema::table('purchase_file_sale_land_moza_overrides', function (Blueprint $table) {
            $table->dropColumn('land_provider');
        });
    }
};
