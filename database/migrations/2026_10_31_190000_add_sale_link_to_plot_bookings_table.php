<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('plot_bookings', function (Blueprint $table) {
            if (! Schema::hasColumn('plot_bookings', 'sale_id')) {
                $table->foreignId('sale_id')->nullable()->after('project_plot_id')->constrained('sales')->nullOnDelete();
            }
            if (! Schema::hasColumn('plot_bookings', 'party_id')) {
                $table->foreignId('party_id')->nullable()->after('customer_id')->constrained('parties')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('plot_bookings', function (Blueprint $table) {
            if (Schema::hasColumn('plot_bookings', 'sale_id')) {
                $table->dropConstrainedForeignId('sale_id');
            }
            if (Schema::hasColumn('plot_bookings', 'party_id')) {
                $table->dropConstrainedForeignId('party_id');
            }
        });
    }
};
