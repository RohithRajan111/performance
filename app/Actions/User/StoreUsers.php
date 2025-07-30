<?php

namespace App\Actions\User;

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Arr;

class StoreUsers
{
    public function handle(array $data): User
    {
        $userData = [
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),

            // SIMPLIFIED: If parent_id is not provided, it will default to null.
            'parent_id' => $data['parent_id'] ?? null,

            // CHANGED: The user's direct manager is now the leave approver.
            // This is a more robust business rule than finding a random HR person.
            'leave_approver_id' => $data['parent_id'] ?? null,

            // ADDED: The team assignment is now part of the user creation.
            // This is more efficient and assumes a `team_id` column on the users table.
            'team_id' => ($data['role'] === 'employee' && isset($data['team_id'])) ? $data['team_id'] : null,

            'image' => null, // Default to null
        ];

        // Handle image upload if it exists
        if (isset($data['image']) && $data['image'] instanceof UploadedFile) {
            $userData['image'] = $data['image']->store('profile_images', 'public');
        }

        // Create the user with the consolidated data
        $user = User::create($userData);

        // Assign role using Spatie - this must be done after creation
        $user->assignRole($data['role']);

        // The old team attachment block is no longer needed.

        return $user;
    }
}
