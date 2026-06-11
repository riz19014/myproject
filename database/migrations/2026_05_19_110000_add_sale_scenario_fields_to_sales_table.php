<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->foreignId('project_file_id')->nullable()->after('project_id')->constrained('project_files')->nullOnDelete();
            $table->string('sale_type', 20)->default('direct')->after('project_file_id');
            $table->string('component', 20)->nullable()->after('sale_type');
            $table->string('plot_type', 30)->nullable()->after('component');
            $table->unsignedInteger('plot_quantity')->default(1)->after('plot_type');
            $table->foreignId('customer_id')->nullable()->after('plot_quantity')->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropConstrainedForeignId('customer_id');
            $table->dropColumn(['plot_quantity', 'plot_type', 'component', 'sale_type']);
            $table->dropConstrainedForeignId('project_file_id');
        });
    }
};
