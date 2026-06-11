<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('project_files', function (Blueprint $table) {
            $table->unsignedInteger('area_acre')->default(0)->after('file_number');
            $table->unsignedInteger('area_kanal')->default(0)->after('area_acre');
            $table->unsignedInteger('area_marla')->default(0)->after('area_kanal');
            $table->unsignedInteger('area_sqft')->default(0)->after('area_marla');
            $table->decimal('land_area_marla', 15, 4)->default(0)->after('area_sqft');
        });
    }

    public function down(): void
    {
        Schema::table('project_files', function (Blueprint $table) {
            $table->dropColumn(['area_acre', 'area_kanal', 'area_marla', 'area_sqft', 'land_area_marla']);
        });
    }
};
