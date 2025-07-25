<?php

namespace App\Actions\Team;

use App\Models\Team;

class DeleteTeam
{
    public function handle(Team $team): void
    {
        // Add logic here if you need to re-assign members, etc.
        $team->delete();
    }
}
