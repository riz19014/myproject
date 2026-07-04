<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('party_sub_categories', function (Blueprint $table) {
            $table->string('unit', 50)->nullable()->after('name');
        });
    }

    public function down(): void
    {
        Schema::table('party_sub_categories', function (Blueprint $table) {
            $table->dropColumn('unit');
        });
    }
};
