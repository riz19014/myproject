<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_partners', function (Blueprint $table) {
            $table->id();
            $table->foreignId('party_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('purchase_file_id')->constrained('purchase_files')->cascadeOnDelete();
            $table->decimal('investment_amount', 15, 2)->default(0);
            $table->decimal('share_percentage', 5, 2)->default(0);
            $table->timestamps();

            $table->unique(
                ['party_id', 'project_id', 'purchase_file_id'],
                'project_partners_party_project_file_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_partners');
    }
};
