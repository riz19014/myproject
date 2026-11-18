<?php

namespace App\Http\Controllers\Society;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Society\Concerns\InteractsWithSocietyProject;
use App\Models\Project;
use App\Support\LandMeasure;
use App\Support\SocietyProject;
use Illuminate\View\View;

class SocietyAcquisitionController extends Controller
{
    use InteractsWithSocietyProject;

    public function index(Project $project): View
    {
        $project = $this->societyProject($project);
        $project->load([
            'landType',
            'purchaseFiles' => fn ($q) => $q
                ->with(['purchaseItems.party', 'dealers'])
                ->withCount('purchaseItems')
                ->orderByDesc('file_date')
                ->orderBy('file_name'),
        ]);

        $purchasedMarla = SocietyProject::purchasedMarla($project);
        $purchaseCost = (float) $project->purchaseItems()->sum('line_total_rs');

        return view('society.acquisition.index', [
            'project' => $project,
            'purchasedMarla' => $purchasedMarla,
            'purchasedLabel' => LandMeasure::formatAkmsLabelFromMarla($purchasedMarla),
            'purchaseCost' => $purchaseCost,
        ]);
    }
}
