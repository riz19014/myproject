<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('file_sale_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('name', 150);
            $table->timestamps();

            $table->unique(['project_id', 'name']);
            $table->index('project_id');
        });

        Schema::table('file_sale_collectives', function (Blueprint $table) {
            $table->foreignId('batch_id')
                ->nullable()
                ->after('project_id')
                ->constrained('file_sale_batches')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('file_sale_collectives', function (Blueprint $table) {
            $table->dropConstrainedForeignId('batch_id');
        });

        Schema::dropIfExists('file_sale_batches');
    }
};
