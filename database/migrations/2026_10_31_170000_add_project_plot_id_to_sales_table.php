<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            if (! Schema::hasColumn('sales', 'project_plot_id')) {
                $table->foreignId('project_plot_id')
                    ->nullable()
                    ->after('project_file_id')
                    ->constrained('project_plots')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            if (Schema::hasColumn('sales', 'project_plot_id')) {
                $table->dropConstrainedForeignId('project_plot_id');
            }
        });
    }
};
