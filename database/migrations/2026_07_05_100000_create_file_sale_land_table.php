<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('file_sale_land', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sale_land_id')->constrained('purchase_files')->cascadeOnDelete();
            $table->timestamps();

            $table->unique('sale_land_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('file_sale_land');
    }
};
