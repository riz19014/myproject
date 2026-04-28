<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->decimal('land_area', 15, 4)->nullable()->after('name');
            $table->string('land_area_unit', 16)->nullable()->after('land_area');
            $table->string('field_type', 16)->nullable()->after('land_area_unit');
            $table->foreignId('land_type_id')->nullable()->after('field_type')->constrained('land_types')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropForeign(['land_type_id']);
            $table->dropColumn(['land_area', 'land_area_unit', 'field_type', 'land_type_id']);
        });
    }
};
