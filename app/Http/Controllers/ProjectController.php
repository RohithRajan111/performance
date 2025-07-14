<?php
namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Team;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;

class ProjectController extends Controller
{
    use AuthorizesRequests;
    /**
     * Show the form for creating a new project.
     */
    public function create()
    {
        // Fetch all teams to pass to the frontend dropdown.
        // We also fetch the related team lead's name for a better UI.
        $teams = Team::with('teamLead:id,name')->get();

        return Inertia::render('Projects/Create', [
            'teams' => $teams,
        ]);
    }

    /**
     * Store a newly created project in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'team_id' => 'required|exists:teams,id',
            'end_date' => 'required|date|after_or_equal:today', // New validation
            'total_hours_required' => 'required|integer|min:1', // New validation
        ]);

        Project::create([
        'name' => $request->name,
        'description' => $request->description,
        'team_id' => $request->team_id,
        'project_manager_id' => Auth::user()->id,
        'status' => 'pending',
        'end_date' => $request->end_date,
        'total_hours_required' => $request->total_hours_required,
    ]);

        return Redirect::route('dashboard')->with('success', 'Project created.');
    }
    public function show(Project $project)
    {
        // Use the ProjectPolicy to ensure only authorized users (PM, TeamLead, Admin) can view.
        $this->authorize('view', $project);

        $tasks = $project->tasks()->with('assignedTo:id,name')->get();
        
        $teamMembers = collect();
        // Only get team members if the user has permission to assign tasks (TeamLead/Admin)
        if (Auth::user()->can('assign tasks')) {
            $teamMembers = $project->team->members;
        }

        return Inertia::render('Projects/Show', [
            'project' => $project,
            'tasks' => $tasks,
            'teamMembers' => $teamMembers,
        ]);
    }
}