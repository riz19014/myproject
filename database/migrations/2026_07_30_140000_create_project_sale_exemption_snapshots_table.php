<?php

use App\Models\Project;
use App\Models\ProjectSaleExemptionSnapshot;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_sale_exemption_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->json('payload');
            $table->timestamps();

            $table->index(['project_id', 'created_at']);
        });

        Project::query()
            ->whereHas('saleExemptionComponents')
            ->with([
                'saleExemptionComponents' => fn ($q) => $q->orderBy('sort_order'),
                'saleExemptionComponents.plotTypes' => fn ($q) => $q->orderBy('sort_order'),
            ])
            ->each(function (Project $project) {
                ProjectSaleExemptionSnapshot::storeFromProject($project);
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_sale_exemption_snapshots');
    }
};
