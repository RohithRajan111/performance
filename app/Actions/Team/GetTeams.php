<?php

namespace App\Actions\Team;

use App\Models\Team;
use App\Models\User;

class GetTeams
{
    public function handle(): array
    {
        return [
            'teams' => Team::with('teamLead:id,name')->withCount('members')->get(),
            'potentialLeads' => User::whereHas('roles', function ($query) {
                $query->where('name', 'team-lead');
            })->get(['id', 'name']),
        ];
    }
}
