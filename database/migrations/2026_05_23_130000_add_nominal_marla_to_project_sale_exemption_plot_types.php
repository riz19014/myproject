<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('project_sale_exemption_plot_types', function (Blueprint $table) {
            $table->decimal('nominal_marla', 10, 4)->nullable()->after('marla_per_plot');
        });

        $defaults = [
            '2_kanal' => 40,
            '1_kanal' => 20,
            '10_marla' => 10,
            '8_marla' => 8,
        ];

        foreach ($defaults as $slug => $nominal) {
            DB::table('project_sale_exemption_plot_types')
                ->where('slug', $slug)
                ->whereNull('nominal_marla')
                ->update(['nominal_marla' => $nominal]);
        }

        DB::table('project_sale_exemption_plot_types')
            ->whereNull('nominal_marla')
            ->update(['nominal_marla' => DB::raw('marla_per_plot')]);
    }

    public function down(): void
    {
        Schema::table('project_sale_exemption_plot_types', function (Blueprint $table) {
            $table->dropColumn('nominal_marla');
        });
    }
};
