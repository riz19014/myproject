<?php

namespace App\Http\Controllers\Society;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Society\Concerns\InteractsWithSocietyProject;
use App\Models\Project;
use App\Support\SocietyReports;
use Illuminate\View\View;

class SocietyReportController extends Controller
{
    use InteractsWithSocietyProject;

    public function index(Project $project): View
    {
        $project = $this->societyProject($project);
        $reports = SocietyReports::build($project);

        return view('society.reports.index', [
            'project' => $project,
            'reports' => $reports,
        ]);
    }
}
