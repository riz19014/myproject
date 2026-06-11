<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_file_sale_land_moza_overrides', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_file_id')->constrained()->cascadeOnDelete();
            $table->string('moza_key', 255);
            $table->string('land_owner', 255)->nullable();
            $table->string('transfer_to', 255)->nullable();
            $table->timestamps();

            $table->unique(['purchase_file_id', 'moza_key'], 'pf_sale_land_moza_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_file_sale_land_moza_overrides');
    }
};
