<?php

namespace App\Actions\User;

use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UpdateUser
{
    public function handle(User $user, array $data): User
    {
        $user->update([
            'name' => $data['name'],
            'email' => $data['email'],
            'team_id' => $data['team_id'] ?? null, // Handle if team is optional
        ]);

        // Only update the password if a new one is provided
        if (!empty($data['password'])) {
            $user->update(['password' => Hash::make($data['password'])]);
        }

        // Sync roles (this will replace existing roles with the new ones)
        if (!empty($data['roles'])) {
            $user->syncRoles($data['roles']);
        }

        return $user;
    }
}