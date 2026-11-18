<?php

namespace App\Support;

use App\Models\Project;
use App\Models\ProjectLandAllocation;
use App\Models\ProjectPlot;

final class SocietyProject
{
    public static function assert(Project $project): void
    {
        abort_if($project->isDha(), 404);
    }

    public static function purchasedMarla(Project $project): float
    {
        $project->loadMissing('purchaseItems');

        return (float) $project->purchaseItems->sum(
            fn ($item) => (float) $item->effectiveLandAreaMarla()
        );
    }

    /**
     * Master-plan saleable area (excludes roads, parks, amenities, wastage, unallocated).
     */
    public static function saleableMarla(Project $project): float
    {
        return (float) ProjectLandAllocation::query()
            ->where('project_id', $project->id)
            ->whereNotIn('category', ProjectLandAllocation::NON_SALEABLE_CATEGORIES)
            ->sum('land_area_marla');
    }

    /**
     * Non-saleable master-plan area (roads, parks, amenities, etc.).
     */
    public static function nonSaleableMarla(Project $project): float
    {
        return (float) ProjectLandAllocation::query()
            ->where('project_id', $project->id)
            ->whereIn('category', ProjectLandAllocation::NON_SALEABLE_CATEGORIES)
            ->sum('land_area_marla');
    }

    /**
     * Land already placed into society sales files (booked / sold plots).
     */
    public static function soldFileMarla(Project $project): float
    {
        return (float) ProjectPlot::query()
            ->where('project_id', $project->id)
            ->whereIn('status', [ProjectPlot::STATUS_BOOKED, ProjectPlot::STATUS_SOLD])
            ->sum('land_area_marla');
    }

    /**
     * Saleable land still available after sales files (saleable − sold).
     */
    public static function remainingLandMarla(Project $project): float
    {
        return self::saleableMarla($project) - self::soldFileMarla($project);
    }
}
