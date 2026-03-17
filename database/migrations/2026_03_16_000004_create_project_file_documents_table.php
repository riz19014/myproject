<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_file_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_file_id')->constrained()->cascadeOnDelete();
            $table->string('name')->nullable();
            $table->string('file_path');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_file_documents');
    }
};
