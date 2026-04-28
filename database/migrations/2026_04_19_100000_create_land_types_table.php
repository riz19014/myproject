<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('land_types', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('party_sub_category_id')->nullable()->constrained('party_sub_categories')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('land_types');
    }
};
