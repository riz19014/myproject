<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('area_acre')->default(0);
            $table->unsignedInteger('area_kanal')->default(0);
            $table->unsignedInteger('area_marla')->default(0);
            $table->unsignedInteger('area_sqft')->default(0);
            $table->decimal('land_area_marla', 15, 4);
            $table->decimal('total_amount', 15, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales');
    }
};
