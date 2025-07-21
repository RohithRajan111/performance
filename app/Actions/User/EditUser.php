<?php

namespace App\Actions\User;

use App\Models\Team;
use App\Models\User;
use Spatie\Permission\Models\Role;

class EditUser
{
    public function handle(User $user)
    {
        // Load the user's current roles to pre-select them in the form
        $user->load('roles');

        return [
            'user'  => $user,
            'roles' => Role::all(),
            'teams' => Team::all(),
        ];
    }
}