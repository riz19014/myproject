<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('file_sale_records', function (Blueprint $table) {
            $table->string('e_stamp_id', 120)->nullable()->change();
            $table->string('purchaser_name')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('file_sale_records', function (Blueprint $table) {
            $table->string('e_stamp_id', 120)->nullable(false)->change();
            $table->string('purchaser_name')->nullable(false)->change();
        });
    }
};
