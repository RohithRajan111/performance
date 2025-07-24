<?php

namespace App\Http\Controllers;

use App\Actions\Project\CreateProject;
use App\Actions\Project\ShowProject;
use App\Actions\Project\StoreProject;
use App\Http\Requests\Project\StoreProjectRequest;
use App\Models\Project;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;

class ProjectController extends Controller
{
    use AuthorizesRequests;

    /**
     * Display a listing of projects and provide data for the creation modal.
     */
    public function index(CreateProject $createProject)
    {
        $user = auth()->user();
        
        if ($user->hasRole('admin')) {
            $projects = Project::with(['projectManager', 'team', 'tasks'])->latest()->get();
        } elseif ($user->hasRole('project-manager')) {
            $projects = Project::where('project_manager_id', $user->id)
                ->with(['projectManager', 'team', 'tasks'])
                ->latest()
                ->get();
        } else {
            $teamIds = $user->teams->pluck('id');
            $projects = Project::whereIn('team_id', $teamIds)
                ->with(['projectManager', 'team', 'tasks'])
                ->latest()
                ->get();
        }
        
        // Use your existing action to get the data needed for the "Create" modal
        $creationData = $createProject->handle();

        return Inertia::render('Projects/Index', [
            'projects' => $projects->map(fn($project) => [
                'id' => $project->id,
                'name' => $project->name,
                'team' => $project->team,
                'project_manager' => $project->projectManager,
                'tasks_count' => $project->tasks->count(),
                'end_date' => $project->end_date,
            ]),
            'teams' => $creationData['teams'],
        ]);
    }

    /**
     * Store a newly created project. This uses your existing action.
     */
    public function store(StoreProjectRequest $request, StoreProject $storeProject)
    {
        $storeProject->handle($request->validated());

        return Redirect::route('projects.index')->with('success', 'Project created successfully.');
    }

    /**
     * Display the specified project. This remains unchanged.
     */
    public function show(Project $project, ShowProject $showProject)
    {
        $data = $showProject->handle($project);
        return Inertia::render('Projects/Show', $data);
    }
}