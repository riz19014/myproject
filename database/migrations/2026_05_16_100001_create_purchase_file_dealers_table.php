<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_file_dealers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_file_id')->constrained()->cascadeOnDelete();
            $table->foreignId('party_id')->constrained()->restrictOnDelete();
            $table->timestamps();

            $table->unique(['purchase_file_id', 'party_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_file_dealers');
    }
};
