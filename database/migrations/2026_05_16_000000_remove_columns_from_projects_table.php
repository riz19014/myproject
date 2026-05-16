<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn([
                'land_area',
                'land_area_unit',
                'field_type',
                'total_amount',
                'description',
                'notes',
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->decimal('land_area', 15, 4)->nullable()->after('name');
            $table->string('land_area_unit', 16)->nullable()->after('land_area');
            $table->string('field_type', 16)->nullable()->after('land_area_unit');
            $table->decimal('total_amount', 15, 2)->nullable()->after('land_type_id');
            $table->text('description')->nullable()->after('total_amount');
            $table->text('notes')->nullable()->after('description');
        });
    }
};
