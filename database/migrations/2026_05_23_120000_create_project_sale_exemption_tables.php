<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_sale_exemption_components', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('slug', 40);
            $table->string('label', 100);
            $table->decimal('pool_percent', 8, 4);
            $table->decimal('marla_per_acre', 8, 4)->default(160);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['project_id', 'slug'], 'proj_sale_exempt_comp_proj_slug_uq');
        });

        Schema::create('project_sale_exemption_plot_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('component_id')->constrained('project_sale_exemption_components')->cascadeOnDelete();
            $table->string('slug', 40);
            $table->string('label', 100);
            $table->decimal('marla_per_plot', 10, 4);
            $table->decimal('share_percent', 8, 4)->default(0);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['project_id', 'component_id', 'slug'], 'proj_sale_exempt_plot_proj_comp_slug_uq');
        });

        Schema::create('project_file_exemption_overrides', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_file_id')->constrained('project_files')->cascadeOnDelete();
            $table->foreignId('component_id')->constrained('project_sale_exemption_components')->cascadeOnDelete();
            $table->decimal('pool_percent', 8, 4);
            $table->timestamps();

            $table->unique(['project_file_id', 'component_id'], 'proj_file_exempt_override_uq');
        });

        Schema::table('projects', function (Blueprint $table) {
            $table->decimal('marla_per_acre', 8, 4)->default(160)->after('land_type_id');
        });

        $projects = \App\Models\Project::query()->pluck('id');
        foreach ($projects as $projectId) {
            $project = \App\Models\Project::query()->find($projectId);
            if ($project) {
                \App\Support\ProjectExemptionDefaults::ensureForProject($project);
            }
        }

        \App\Models\ProjectFile::query()->with('project')->each(function ($file) {
            \App\Support\ProjectExemptionDefaults::syncLegacyFileOverrides($file);
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn('marla_per_acre');
        });

        Schema::dropIfExists('project_file_exemption_overrides');
        Schema::dropIfExists('project_sale_exemption_plot_types');
        Schema::dropIfExists('project_sale_exemption_components');
    }
};
