<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('file_name');
            $table->timestamps();

            $table->unique(['project_id', 'file_name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_files');
    }
};
