<?php

namespace App\Http\Controllers;

use App\Actions\Project\CreateProject;
use App\Actions\Project\ShowProject;
use App\Actions\Project\StoreProject;
use App\Http\Requests\Project\StoreProjectRequest;
use App\Models\Project;
use App\Models\User; // <-- Import User model
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth; // <-- Import Auth facade
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;

class ProjectController extends Controller
{
    use AuthorizesRequests;

    /**
     * Display a listing of projects and provide data for the creation modal.
     */
    public function index(CreateProject $createProject): Response
    {
        $user = Auth::user();
        
        $query = Project::with(['projectManager', 'team', 'tasks'])->latest();

        // Role-based scoping for viewing projects
        if ($user->hasRole('project-manager')) {
            $query->where('project_manager_id', $user->id);
        } elseif (!($user->hasRole('admin') || $user->hasRole('hr'))) { // Assuming HR can also see all
            // For team leads and employees, scope to their teams
            $teamIds = $user->teams->pluck('id');
            $query->whereIn('team_id', $teamIds);
        }
        
        $projects = $query->get();
        
        // This action now returns an array with both 'teams' and 'projectManagers'
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
            // The controller now correctly receives both keys from the action.
            'teams' => $creationData['teams'],
            'projectManagers' => $creationData['projectManagers'],
        ]);
    }

    /**
     * Store a newly created project.
     */
    public function store(StoreProjectRequest $request, StoreProject $storeProject)
    {
        $storeProject->handle($request->validated());
        return Redirect::route('projects.index')->with('success', 'Project created successfully.');
    }

    /**
     * Display the specified project with role-aware assignable users.
     */
    public function show(Project $project, ShowProject $showProject): Response
    {
        $projectData = $showProject->handle($project);
        return Inertia::render('Projects/Show', $projectData);
    }
}