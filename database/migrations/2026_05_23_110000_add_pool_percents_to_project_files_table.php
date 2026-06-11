<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('project_files', function (Blueprint $table) {
            $table->decimal('residential_pool_percent', 8, 4)->default(25)->after('land_area_marla');
            $table->decimal('commercial_pool_percent', 8, 4)->default(3.49)->after('residential_pool_percent');
        });
    }

    public function down(): void
    {
        Schema::table('project_files', function (Blueprint $table) {
            $table->dropColumn(['residential_pool_percent', 'commercial_pool_percent']);
        });
    }
};
