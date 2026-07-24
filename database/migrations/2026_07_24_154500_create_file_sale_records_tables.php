<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('file_sale_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('purchase_file_id')->constrained('purchase_files')->cascadeOnDelete();
            $table->foreignId('sale_id')->nullable()->constrained('sales')->nullOnDelete();
            $table->foreignId('day_book_entry_id')->nullable()->constrained('day_book_entries')->nullOnDelete();
            $table->string('e_stamp_id', 120);
            $table->string('land_owner')->nullable();
            $table->string('land_provider')->nullable();
            $table->string('purchaser_name');
            $table->string('moza')->nullable();
            $table->string('khasra')->nullable();
            $table->string('khewat_no')->nullable();
            $table->string('khatooni_no')->nullable();
            $table->string('component', 40)->nullable();
            $table->string('plot_type', 40)->nullable();
            $table->unsignedInteger('plot_quantity')->default(1);
            $table->decimal('land_area_marla', 15, 4)->default(0);
            $table->decimal('total_amount', 15, 2)->nullable();
            $table->string('status', 20)->default('pending');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['project_id', 'status']);
            $table->index(['purchase_file_id', 'status']);
        });

        Schema::create('file_sale_record_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('file_sale_record_id')->constrained('file_sale_records')->cascadeOnDelete();
            $table->string('name')->nullable();
            $table->string('file_path');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('file_sale_record_documents');
        Schema::dropIfExists('file_sale_records');
    }
};
