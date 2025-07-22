<?php

namespace App\Http\Controllers;

use App\Actions\Team\GetTeams;
use App\Actions\Team\StoreTeams;
use App\Http\Requests\Team\StoreTeamRequest;

use App\Actions\Team\UpdateTeam;
use App\Actions\Team\DeleteTeam;
use App\Http\Requests\Team\UpdateTeamRequest;
use Inertia\Inertia;
use Illuminate\Support\Facades\Redirect;
use App\Models\Team;

class TeamController extends Controller
{
    public function index(GetTeams $getTeams)
    {
        return Inertia::render('Admin/Teams/Index', $getTeams->handle());
    }

    public function store(StoreTeamRequest $request, StoreTeams $storeTeams)
    {
        $storeTeams->handle($request->validated());

        return Redirect::route('teams.index')->with('success', 'Team created successfully.');
    }

     public function update(UpdateTeamRequest $request, Team $team, UpdateTeam $updateTeam)
    {
        $updateTeam->handle($team, $request->validated());
        return Redirect::route('teams.index')->with('success', 'Team updated successfully.');
    }
    public function destroy(Team $team, DeleteTeam $deleteTeam)
    {
        $deleteTeam->handle($team);
        return Redirect::route('teams.index')->with('success', 'Team deleted successfully.');
    }
}
