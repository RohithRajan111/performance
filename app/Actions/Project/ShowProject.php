<?php

namespace App\Actions\Project;

use App\Models\Project;

use App\Models\User; 
use Illuminate\Support\Facades\Auth;
use Illuminate\Auth\Access\AuthorizationException;

class ShowProject
{
    /**
     * @throws AuthorizationException
     */
    public function handle(Project $project): array
    {
        Auth::user()->can('view', $project) || abort(403);

        $project->load('team.members', 'team.teamLead');

        $tasks = $project->tasks()->with('assignedTo:id,name')->get();

        $teamMembers = collect();
        if (Auth::user()->can('assign tasks') && $project->team) {
            $teamMembers = $project->team->members
                ->push($project->team->teamLead)
                ->filter() 
                ->unique('id');
        }

        return [
            'project' => $project,
            'tasks' => $tasks,
            'teamMembers' => $teamMembers,
        ];
    }
}