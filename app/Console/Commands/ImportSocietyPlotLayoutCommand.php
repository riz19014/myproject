<?php

namespace App\Console\Commands;

use App\Models\Project;
use App\Models\ProjectBlock;
use App\Models\ProjectLandAllocation;
use App\Models\ProjectPlot;
use App\Support\LandMeasure;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportSocietyPlotLayoutCommand extends Command
{
    protected $signature = 'society:import-plot-layout
                            {project=14 : Project ID}
                            {--fresh : Delete existing society blocks/plots/allocations for this project first}';

    protected $description = 'Import Rohi Nala / Hudiara Drain layout (plan 02) into society blocks, plots, and master plan';

    public function handle(): int
    {
        $projectId = (int) $this->argument('project');
        $project = Project::query()->find($projectId);
        if (! $project) {
            $this->error('Project '.$projectId.' not found.');

            return self::FAILURE;
        }

        if ($project->isDha()) {
            $this->warn('Project is DHA; society modules expect is_dha=0. Continuing anyway.');
        }

        if ($this->option('fresh')) {
            DB::transaction(function () use ($project) {
                ProjectPlot::query()->where('project_id', $project->id)->delete();
                ProjectBlock::query()->where('project_id', $project->id)->delete();
                ProjectLandAllocation::query()->where('project_id', $project->id)->delete();
            });
            $this->info('Cleared existing society blocks, plots, and allocations.');
        }

        $plotsSpec = $this->layoutPlots();

        DB::transaction(function () use ($project, $plotsSpec) {
            $west = ProjectBlock::query()->updateOrCreate(
                ['project_id' => $project->id, 'name' => 'West (left of 18\' road)'],
                [
                    'sector' => 'Rohi Nala Rd / Hudiara Drain — plan 02',
                    'category' => 'residential',
                    'notes' => 'Plots 1–7. Central road ROAD WIDE 18\'. Access via Rohi Nala Rd (Gajumata ↔ Bedian Rd).',
                    'sort_order' => 1,
                ]
            );
            $east = ProjectBlock::query()->updateOrCreate(
                ['project_id' => $project->id, 'name' => 'East (right of 18\' road)'],
                [
                    'sector' => 'Rohi Nala Rd / Hudiara Drain — plan 02',
                    'category' => 'residential',
                    'notes' => 'Plots 8–14. Central road ROAD WIDE 18\'.',
                    'sort_order' => 2,
                ]
            );

            $residentialMarla = 0.0;
            foreach ($plotsSpec as $spec) {
                $blockId = $spec['side'] === 'west' ? $west->id : $east->id;
                $landMarla = (float) $spec['marla'] + ((int) $spec['extra_sqft'] / LandMeasure::SQFT_PER_MARLA);
                [$acre, $kanal, $marla, $sqft] = $this->decomposeMarla($landMarla);
                $residentialMarla += $landMarla;

                $plot = ProjectPlot::query()->firstOrNew([
                    'project_id' => $project->id,
                    'plot_number' => (string) $spec['number'],
                ]);
                $plot->fill([
                    'project_block_id' => $blockId,
                    'category' => 'residential',
                    'area_acre' => $acre,
                    'area_kanal' => $kanal,
                    'area_marla' => $marla,
                    'area_sqft' => $sqft,
                    'land_area_marla' => round($landMarla, 6),
                    'status' => $plot->exists ? $plot->status : ProjectPlot::STATUS_AVAILABLE,
                    'notes' => $spec['notes'],
                ]);
                $plot->save();
            }

            // Small top strips on plan (SQFT 42 + SQFT 45) → amenity
            $amenitySqft = 42 + 45;
            $this->upsertAllocation($project->id, 'amenity', 0, 0, 0, $amenitySqft, 'Plan top strips SQFT 42 + SQFT 45');

            // Residential = sum of saleable plots
            $this->upsertAllocationFromMarla(
                $project->id,
                'residential',
                $residentialMarla,
                'Sum of plots 1–14 from plan 02'
            );

            // Central ROAD WIDE 18' — approximate from corridor length of layout (~180–200 ft × 18 ft)
            // Use ~16 marla as planning placeholder; adjust on Master plan screen if needed.
            $this->upsertAllocationFromMarla(
                $project->id,
                'road',
                16.0,
                'ROAD WIDE 18\' central spine (approx.; refine from survey)'
            );

            foreach (['commercial', 'park', 'wastage', 'unallocated'] as $cat) {
                if (! ProjectLandAllocation::query()->where('project_id', $project->id)->where('category', $cat)->exists()) {
                    $this->upsertAllocation($project->id, $cat, 0, 0, 0, 0, null);
                }
            }
        });

        $count = ProjectPlot::query()->where('project_id', $project->id)->count();
        $this->info('Imported layout for project '.$project->id.' ('.$project->name.'). Plots now: '.$count);
        $this->line('Open: /projects/'.$project->id.'/society/blocks and /society/master-plan');

        return self::SUCCESS;
    }

    /**
     * @return list<array{number:int,side:string,marla:float,extra_sqft:int,notes:string}>
     */
    private function layoutPlots(): array
    {
        return [
            ['number' => 1, 'side' => 'west', 'marla' => 3.5, 'extra_sqft' => 0, 'notes' => '3.5 MARLA. Front/edges ~37\'-5", 21\'-8", 21\'-4"'],
            ['number' => 2, 'side' => 'west', 'marla' => 3.5, 'extra_sqft' => 0, 'notes' => '3.5 MARLA. ~35\'-10", 22\'-7", 22\'-6"'],
            ['number' => 3, 'side' => 'west', 'marla' => 3, 'extra_sqft' => 155, 'notes' => '3 MARLA SQFT 155. ~34\'-2", 25\'-1", 25\''],
            ['number' => 4, 'side' => 'west', 'marla' => 4, 'extra_sqft' => 0, 'notes' => '4 MARLA (highlighted). Irregular ~17\'-11" / 32\'-3", 13\'-8", 19\'-6"'],
            ['number' => 5, 'side' => 'west', 'marla' => 4, 'extra_sqft' => 0, 'notes' => '4 MARLA (highlighted). ~48\'-1", 19\'-2", 19\'-1"'],
            ['number' => 6, 'side' => 'west', 'marla' => 4, 'extra_sqft' => 0, 'notes' => '4 MARLA. ~46\'-5", 19\'-10", 19\'-9"'],
            ['number' => 7, 'side' => 'west', 'marla' => 10, 'extra_sqft' => 185, 'notes' => '10 MARLA SQFT 185. Corner ~44\'-9", 64\'-4", 50\'-10", 41\'-6"'],
            ['number' => 8, 'side' => 'east', 'marla' => 4, 'extra_sqft' => 0, 'notes' => '4 MARLA. ~45\', 20\'-2", 19\'-10"'],
            ['number' => 9, 'side' => 'east', 'marla' => 4, 'extra_sqft' => 0, 'notes' => '4 MARLA. ~45\', 20\', 20\''],
            ['number' => 10, 'side' => 'east', 'marla' => 4, 'extra_sqft' => 0, 'notes' => '4 MARLA. ~20\' × 20\' sides'],
            ['number' => 11, 'side' => 'east', 'marla' => 4, 'extra_sqft' => 0, 'notes' => '4 MARLA. ~20\' × 20\' sides'],
            ['number' => 12, 'side' => 'east', 'marla' => 4, 'extra_sqft' => 0, 'notes' => '4 MARLA. ~20\' × 20\' sides'],
            ['number' => 13, 'side' => 'east', 'marla' => 6, 'extra_sqft' => 182, 'notes' => '6 MARLA SQFT 182. ~22\'-7", 71\'-8", 64\', 23\'-10"'],
            ['number' => 14, 'side' => 'east', 'marla' => 6, 'extra_sqft' => 0, 'notes' => '6 MARLA. ~22\'-5", 56\'-6", 23\'-8"'],
        ];
    }

    private function upsertAllocation(
        int $projectId,
        string $category,
        int $acre,
        int $kanal,
        int $marla,
        int $sqft,
        ?string $notes
    ): void {
        $row = ProjectLandAllocation::query()->firstOrNew([
            'project_id' => $projectId,
            'category' => $category,
        ]);
        $row->area_acre = $acre;
        $row->area_kanal = $kanal;
        $row->area_marla = $marla;
        $row->area_sqft = $sqft;
        $row->notes = $notes;
        $row->syncMarlaFromAkms();
        $row->save();
    }

    private function upsertAllocationFromMarla(int $projectId, string $category, float $totalMarla, ?string $notes): void
    {
        [$a, $k, $m, $s] = $this->decomposeMarla($totalMarla);
        $this->upsertAllocation($projectId, $category, $a, $k, $m, $s, $notes);
    }

    /**
     * @return array{0:int,1:int,2:int,3:int}
     */
    private function decomposeMarla(float $totalMarla): array
    {
        $eps = 1e-6;
        $whole = (int) floor($totalMarla + $eps);
        $a = intdiv($whole, 160);
        $r = $whole - $a * 160;
        $k = intdiv($r, 20);
        $m = $r - $k * 20;
        $frac = max(0.0, $totalMarla - $whole);
        $sqft = (int) round($frac * LandMeasure::SQFT_PER_MARLA);

        return [$a, $k, $m, $sqft];
    }
}
