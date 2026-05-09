<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('land_cuttings');

        Schema::create('land_cuttings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_id')->constrained('sales')->cascadeOnDelete();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('cutting_type', 50);
            $table->unsignedInteger('area_acre')->default(0);
            $table->unsignedInteger('area_kanal')->default(0);
            $table->unsignedInteger('area_marla')->default(0);
            $table->unsignedInteger('area_sqft')->default(0);
            $table->decimal('land_area_marla', 15, 4);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('land_cuttings');

        Schema::create('land_cuttings', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });
    }
};
