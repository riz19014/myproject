<?php

namespace App\Support;

use App\Models\Project;
use App\Models\ProjectFile;
use App\Models\ProjectSaleExemptionComponent;
use App\Models\ProjectSaleExemptionPlotType;
use Illuminate\Support\Collection;

final class SaleExemptionConfig
{
    private Project $project;

    private ?ProjectFile $file;

    /** @var Collection<int, ProjectSaleExemptionComponent> */
    private Collection $components;

    private ?float $marlaPerAcreOverride = null;

    public function __construct(Project $project, ?ProjectFile $file = null, bool $skipLoad = false)
    {
        if (! $skipLoad) {
            ProjectExemptionDefaults::ensureForProject($project);
            if ($file) {
                ProjectExemptionDefaults::syncLegacyFileOverrides($file);
                $file->loadMissing('exemptionOverrides');
            }
        }

        $this->project = $project;
        $this->file = $file;
        $this->components = $skipLoad
            ? collect()
            : $project->saleExemptionComponents()
                ->with(['plotTypes' => fn ($q) => $q->orderBy('sort_order')])
                ->orderBy('sort_order')
                ->get();
    }

    /**
     * @param  array{marla_per_acre?: float, components?: list<array<string, mixed>>}  $data
     */
    public static function fromSnapshotData(Project $project, array $data): self
    {
        $instance = new self($project, null, true);
        $instance->marlaPerAcreOverride = (float) ($data['marla_per_acre'] ?? $project->marla_per_acre ?? 160);
        $instance->components = collect($data['components'] ?? [])->map(function (array $row) use ($instance) {
            $component = new ProjectSaleExemptionComponent([
                'slug' => $row['slug'],
                'label' => $row['label'],
                'pool_percent' => $row['pool_percent'],
                'marla_per_acre' => $instance->marlaPerAcreOverride,
            ]);
            $component->setRelation('plotTypes', collect($row['plot_types'] ?? [])->map(fn (array $plotRow) => new ProjectSaleExemptionPlotType([
                'slug' => $plotRow['slug'],
                'label' => $plotRow['label'],
                'marla_per_plot' => $plotRow['marla_per_plot'],
                'nominal_marla' => $plotRow['nominal_marla'],
                'share_percent' => $plotRow['share_percent'],
            ])));

            return $component;
        });

        return $instance;
    }

    public static function forFile(ProjectFile $file): self
    {
        $file->loadMissing('project');

        return new self($file->project, $file);
    }

    public static function forProject(Project $project): self
    {
        return new self($project);
    }

    /** @return Collection<int, ProjectSaleExemptionComponent> */
    public function components(): Collection
    {
        return $this->components;
    }

    public function marlaPerAcreLand(): float
    {
        if ($this->marlaPerAcreOverride !== null) {
            return $this->marlaPerAcreOverride;
        }

        return (float) ($this->project->marla_per_acre ?? 160);
    }

    public function poolPercent(string $componentSlug): float
    {
        $component = $this->findComponent($componentSlug);
        if (! $component) {
            return 0.0;
        }

        if ($this->file) {
            $override = $this->file->exemptionOverrides
                ->firstWhere('component_id', $component->id);
            if ($override) {
                return (float) $override->pool_percent;
            }
        }

        return (float) $component->pool_percent;
    }

    public function poolMarla(float $fileMarla, string $componentSlug): float
    {
        return round($fileMarla * $this->poolPercent($componentSlug) / 100, 4);
    }

    /**
     * @return array<string, float>
     */
    public function poolsForFileMarla(float $fileMarla): array
    {
        $pools = [];
        foreach ($this->components as $component) {
            $pools[$component->slug] = $this->poolMarla($fileMarla, $component->slug);
        }

        return $pools;
    }

    /**
     * @return array<string, mixed>
     */
    public function poolsSummary(float $fileMarla): array
    {
        $pools = $this->poolsForFileMarla($fileMarla);
        $perAcre = [];
        foreach ($this->components as $component) {
            $perAcre[$component->slug] = round(
                (float) $component->pool_percent / 100 * (float) $component->marla_per_acre,
                4
            );
        }

        return [
            'pools' => $pools,
            'distribution_per_acre' => $perAcre,
            'marla_per_acre_land' => $this->marlaPerAcreLand(),
        ];
    }

    public function findComponent(string $slug): ?ProjectSaleExemptionComponent
    {
        return $this->components->firstWhere('slug', $slug);
    }

    public function findPlotType(string $componentSlug, string $plotSlug): ?ProjectSaleExemptionPlotType
    {
        $component = $this->findComponent($componentSlug);

        return $component?->plotTypes->firstWhere('slug', $plotSlug);
    }

    public function plotMarla(string $componentSlug, string $plotSlug): float
    {
        $plot = $this->findPlotType($componentSlug, $plotSlug);

        return $plot ? (float) $plot->marla_per_plot : 0.0;
    }

    public function plotLabel(string $componentSlug, string $plotSlug): string
    {
        $plot = $this->findPlotType($componentSlug, $plotSlug);

        return $plot?->label ?? $plotSlug;
    }

    /**
     * @return array<string, string>
     */
    public function plotOptionsForComponent(string $componentSlug): array
    {
        $component = $this->findComponent($componentSlug);
        if (! $component) {
            return [];
        }

        return $component->plotTypes->mapWithKeys(fn ($p) => [$p->slug => $p->label])->all();
    }

    public function componentSlugs(): array
    {
        return $this->components->pluck('slug')->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function toFrontendJson(): array
    {
        return $this->components->map(function (ProjectSaleExemptionComponent $c) {
            $poolPct = $this->file ? $this->poolPercent($c->slug) : (float) $c->pool_percent;

            return [
                'slug' => $c->slug,
                'label' => $c->label,
                'pool_percent' => $poolPct,
                'project_pool_percent' => (float) $c->pool_percent,
                'marla_per_acre' => (float) $c->marla_per_acre,
                'distribution_marla_per_acre' => round($poolPct / 100 * (float) $c->marla_per_acre, 4),
                'plot_types' => $c->plotTypes->map(fn ($p) => [
                    'slug' => $p->slug,
                    'label' => $p->label,
                    'marla' => (float) $p->marla_per_plot,
                    'nominal_marla' => SaleExemptionFileCalculator::nominalMarlaForPlot($p),
                    'share_percent' => (float) $p->share_percent,
                ])->values()->all(),
            ];
        })->values()->all();
    }
}
