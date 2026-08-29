<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('file_sale_collectives', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('name', 150);
            $table->string('status', 20)->default('open');
            $table->json('exemption_payload')->nullable();
            $table->decimal('total_land_marla', 15, 4)->default(0);
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->unique(['project_id', 'name']);
            $table->index(['project_id', 'status']);
        });

        Schema::table('file_sale_land', function (Blueprint $table) {
            $table->foreignId('collective_id')
                ->nullable()
                ->after('sale_land_id')
                ->constrained('file_sale_collectives')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('file_sale_land', function (Blueprint $table) {
            $table->dropConstrainedForeignId('collective_id');
        });

        Schema::dropIfExists('file_sale_collectives');
    }
};
