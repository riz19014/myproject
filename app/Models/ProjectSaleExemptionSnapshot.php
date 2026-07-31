<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectSaleExemptionSnapshot extends Model
{
    protected $fillable = [
        'project_id',
        'payload',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function components(): array
    {
        return $this->payload['components'] ?? [];
    }

    public function marlaPerAcre(): float
    {
        return (float) ($this->payload['marla_per_acre'] ?? 160);
    }

    public function summaryLabel(): string
    {
        $parts = [];
        foreach ($this->components() as $component) {
            $label = trim((string) ($component['label'] ?? $component['slug'] ?? ''));
            $pct = rtrim(rtrim(number_format((float) ($component['pool_percent'] ?? 0), 4, '.', ''), '0'), '.');
            $parts[] = $label.' '.$pct.'%';
        }

        return $parts !== [] ? implode(' · ', $parts) : '—';
    }

    public static function storeFromProject(Project $project): self
    {
        $project->loadMissing([
            'saleExemptionComponents' => fn ($q) => $q->orderBy('sort_order'),
            'saleExemptionComponents.plotTypes' => fn ($q) => $q->orderBy('sort_order'),
        ]);

        return self::query()->create([
            'project_id' => $project->id,
            'payload' => self::payloadFromProject($project),
        ]);
    }

    /**
     * @return array{marla_per_acre: float, components: list<array<string, mixed>>}
     */
    public static function payloadFromProject(Project $project): array
    {
        return [
            'marla_per_acre' => (float) ($project->marla_per_acre ?? 160),
            'components' => $project->saleExemptionComponents->map(function (ProjectSaleExemptionComponent $component) {
                return [
                    'slug' => $component->slug,
                    'label' => $component->label,
                    'pool_percent' => (float) $component->pool_percent,
                    'plot_types' => $component->plotTypes->map(fn (ProjectSaleExemptionPlotType $plot) => [
                        'slug' => $plot->slug,
                        'label' => $plot->label,
                        'marla_per_plot' => (float) $plot->marla_per_plot,
                        'nominal_marla' => (float) $plot->nominal_marla,
                        'share_percent' => (float) $plot->share_percent,
                    ])->values()->all(),
                ];
            })->values()->all(),
        ];
    }
}
