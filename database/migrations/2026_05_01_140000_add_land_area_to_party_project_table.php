<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('party_project', function (Blueprint $table) {
            $table->decimal('land_area', 15, 4)->nullable()->after('party_id');
            $table->string('land_area_unit', 16)->nullable()->after('land_area');
        });
    }

    public function down(): void
    {
        Schema::table('party_project', function (Blueprint $table) {
            $table->dropColumn(['land_area', 'land_area_unit']);
        });
    }
};
