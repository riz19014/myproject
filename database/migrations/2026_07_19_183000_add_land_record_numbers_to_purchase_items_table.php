<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_items', function (Blueprint $table) {
            $table->string('khewat_no', 255)->nullable()->after('khasra');
            $table->string('khatooni_no', 255)->nullable()->after('khewat_no');
            $table->string('intiqal_no', 255)->nullable()->after('khatooni_no');
        });
    }

    public function down(): void
    {
        Schema::table('purchase_items', function (Blueprint $table) {
            $table->dropColumn(['khewat_no', 'khatooni_no', 'intiqal_no']);
        });
    }
};
