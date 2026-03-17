<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('day_book_entries', function (Blueprint $table) {
            $table->id();
            $table->date('entry_date');
            $table->string('type'); // cash_in, cash_out
            $table->decimal('amount', 15, 2);
            $table->text('description')->nullable();
            // link_type: office, project, land, plot, factory, customer
            $table->string('link_type')->nullable();
            $table->unsignedBigInteger('link_id')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('day_book_entries');
    }
};
