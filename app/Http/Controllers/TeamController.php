<?php
namespace App\Http\Controllers;

use App\Models\Team;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Redirect;

class TeamController extends Controller
{
    /**
     * Display a listing of the teams.
     */
    public function index()
    {
        return Inertia::render('Admin/Teams/Index', [
            // Eager load the team lead and members count for an efficient display
            'teams' => Team::with('teamLead:id,name')->withCount('members')->get(),
            // Get a list of potential team leads to choose from
            'potentialLeads' => User::whereHas('roles', function ($query) {
                $query->where('name', 'team-lead');
            })->get(['id', 'name']),
        ]);
    }

    /**
     * Store a newly created team in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:teams,name',
            
            // --- THIS IS THE CORRECTED VALIDATION ---
            'team_lead_id' => [
                'required',
                'exists:users,id',
                // This is our custom rule using a closure
                function ($attribute, $value, $fail) {
                    // Check if a team already exists with this user as the lead.
                    $alreadyLeadsTeam = Team::where('team_lead_id', $value)->exists();

                    if ($alreadyLeadsTeam) {
                        // If it exists, fail the validation with a specific message.
                        $fail('This user is already leading another team.');
                    }
                },
            ],
        ]);

        $team = Team::create([
            'name' => $request->name,
            'team_lead_id' => $request->team_lead_id,
        ]);

        // Automatically add the team lead as a member of their own team
        $team->members()->attach($request->team_lead_id);

        return Redirect::route('teams.index')->with('success', 'Team created successfully.');
    }
}