<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('project_files', function (Blueprint $table) {
            $table->decimal('sale_amount_per_acre', 16, 2)->nullable()->after('plot_sale_rates_per_acre');
        });
    }

    public function down(): void
    {
        Schema::table('project_files', function (Blueprint $table) {
            $table->dropColumn('sale_amount_per_acre');
        });
    }
};
