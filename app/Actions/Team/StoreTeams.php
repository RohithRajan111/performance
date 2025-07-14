<?php

namespace App\Actions\Team;

use App\Models\Team;

class StoreTeams
{
    public function handle(array $data): Team
    {
        $team = Team::create([
            'name' => $data['name'],
            'team_lead_id' => $data['team_lead_id'],
        ]);

        // Add team lead as member
        $team->members()->attach($data['team_lead_id']);

        return $team;
    }
}
