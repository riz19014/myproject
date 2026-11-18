<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plot_booking_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plot_booking_id')->constrained('plot_bookings')->cascadeOnDelete();
            $table->string('name');
            $table->string('file_path');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plot_booking_documents');
    }
};
