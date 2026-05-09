<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('party_id')->constrained()->restrictOnDelete();
            $table->string('moza', 255)->nullable();
            $table->string('khasra', 255)->nullable();
            $table->unsignedInteger('area_acre')->default(0);
            $table->unsignedInteger('area_kanal')->default(0);
            $table->unsignedInteger('area_marla')->default(0);
            $table->unsignedInteger('area_sqft')->default(0);
            $table->decimal('land_area_marla', 15, 4);
            $table->decimal('amount_per_acre', 15, 2);
            $table->decimal('line_total_rs', 15, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_items');
    }
};
