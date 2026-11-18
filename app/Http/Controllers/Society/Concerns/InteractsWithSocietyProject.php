<?php

namespace App\Http\Controllers\Society\Concerns;

use App\Models\Project;
use App\Support\SocietyProject;

trait InteractsWithSocietyProject
{
    protected function societyProject(Project $project): Project
    {
        SocietyProject::assert($project);

        return $project;
    }

    protected function authorizeSocietyManage(): void
    {
        abort_unless(auth()->user()?->canManageSociety(), 403);
    }

    protected function authorizeSocietyApprove(): void
    {
        abort_unless(auth()->user()?->canApproveSociety(), 403);
    }
}
