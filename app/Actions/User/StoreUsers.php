<?php

namespace App\Actions\User;

use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\UploadedFile;

class StoreUsers
{
    public function handle(array $data): User
    {
        // Find the first HR user to use as leave approver
        $hrApprover = User::role('hr')->first();

        // Handle image upload
        $imagePath = null;
        if (isset($data['image']) && $data['image'] instanceof UploadedFile) {
            $imagePath = $data['image']->store('profile_images', 'public');
        }

        // Create the user
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'parent_id' => $data['parent_id'] ?? null,
            'leave_approver_id' => $hrApprover?->id,
            'image' => $imagePath,
        ]);

        // Assign role using Spatie
        $user->assignRole($data['role']);

        // If employee, link them to a team
        if ($data['role'] === 'employee' && isset($data['team_id'])) {
            $team = Team::find($data['team_id']);
            if ($team) {
                $team->members()->attach($user->id);
            }
        }

        return $user;
    }
}