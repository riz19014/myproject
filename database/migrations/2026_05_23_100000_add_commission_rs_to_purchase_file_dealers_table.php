<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_file_dealers', function (Blueprint $table) {
            $table->unsignedBigInteger('commission_rs')->nullable()->after('party_id');
        });
    }

    public function down(): void
    {
        Schema::table('purchase_file_dealers', function (Blueprint $table) {
            $table->dropColumn('commission_rs');
        });
    }
};
