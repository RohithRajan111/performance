<?php

namespace App\Actions\Team;

use App\Models\Team;
use Illuminate\Support\Facades\DB;

class UpdateTeam
{
   
    public function handle(Team $team, array $data): Team
    {
        $oldLeadId = $team->team_lead_id;
        $newLeadId = $data['team_lead_id'];

        DB::transaction(function () use ($team, $data, $oldLeadId, $newLeadId) {
            
            $team->update($data);

            if ($oldLeadId !== $newLeadId) {
                
                if ($oldLeadId) {
                    $team->members()->detach($oldLeadId);
                }

                if ($newLeadId) {
                    $team->members()->syncWithoutDetaching([$newLeadId]);
                }
            }
        });

        return $team;
    }
}