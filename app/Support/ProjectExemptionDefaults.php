<?php

namespace App\Support;

use App\Models\Project;
use App\Models\ProjectSaleExemptionComponent;
use App\Models\ProjectSaleExemptionPlotType;

final class ProjectExemptionDefaults
{
  /**
     * @return array<int, array{slug: string, label: string, pool_percent: float, plots: array<int, array{slug: string, label: string, marla: float, nominal: float, share: float}>}>
     */
    public static function componentTemplates(): array
    {
        return [
            [
                'slug' => SaleExemptionRules::COMPONENT_RESIDENTIAL,
                'label' => 'Residential',
                'pool_percent' => 25.0,
                'plots' => [
                    ['slug' => '2_kanal', 'label' => '2 Kanal file', 'marla' => 16.0, 'nominal' => 40.0, 'share' => 40.0],
                    ['slug' => '1_kanal', 'label' => '1 Kanal file', 'marla' => 14.0, 'nominal' => 20.0, 'share' => 35.0],
                    ['slug' => '10_marla', 'label' => '10 Marla file', 'marla' => 10.0, 'nominal' => 10.0, 'share' => 25.0],
                ],
            ],
            [
                'slug' => SaleExemptionRules::COMPONENT_COMMERCIAL,
                'label' => 'Commercial',
                'pool_percent' => 3.49,
                'plots' => [
                    ['slug' => '8_marla', 'label' => '8 Marla file', 'marla' => 5.584, 'nominal' => 8.0, 'share' => 100.0],
                ],
            ],
        ];
    }

    public static function ensureForProject(Project $project): void
    {
        if ($project->saleExemptionComponents()->exists()) {
            return;
        }

        $marlaPerAcre = (float) ($project->marla_per_acre ?? 160);

        foreach (self::componentTemplates() as $i => $template) {
            $component = $project->saleExemptionComponents()->create([
                'slug' => $template['slug'],
                'label' => $template['label'],
                'pool_percent' => $template['pool_percent'],
                'marla_per_acre' => $marlaPerAcre,
                'sort_order' => $i,
            ]);

            foreach ($template['plots'] as $j => $plot) {
                ProjectSaleExemptionPlotType::query()->create([
                    'project_id' => $project->id,
                    'component_id' => $component->id,
                    'slug' => $plot['slug'],
                    'label' => $plot['label'],
                    'marla_per_plot' => $plot['marla'],
                    'nominal_marla' => $plot['nominal'],
                    'share_percent' => $plot['share'],
                    'sort_order' => $j,
                ]);
            }
        }
    }

    /** Migrate legacy file pool columns into overrides when present. */
    public static function syncLegacyFileOverrides(\App\Models\ProjectFile $file): void
    {
        $project = $file->project;
        if (! $project) {
            return;
        }

        self::ensureForProject($project);

        $map = [
            SaleExemptionRules::COMPONENT_RESIDENTIAL => $file->residential_pool_percent,
            SaleExemptionRules::COMPONENT_COMMERCIAL => $file->commercial_pool_percent,
        ];

        foreach ($map as $slug => $percent) {
            if ($percent === null) {
                continue;
            }
            $component = $project->saleExemptionComponents()->where('slug', $slug)->first();
            if (! $component) {
                continue;
            }
            $file->exemptionOverrides()->updateOrCreate(
                ['component_id' => $component->id],
                ['pool_percent' => round((float) $percent, 4)]
            );
        }
    }
}
