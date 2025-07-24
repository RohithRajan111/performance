<?php

namespace App\Actions\User;

use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UpdateUser
{
    public function handle(User $user, array $data): User
    {
        // Handle image upload if provided
        if (isset($data['image']) && $data['image'] instanceof \Illuminate\Http\UploadedFile) {
            $imagePath = $data['image']->store('profile_images', 'public');
            $data['image'] = $imagePath; // Update the data array with the new image path
        } else {
            $data['image'] = $user->image; // Keep existing image if not provided
        }
        // Update the user with the provided data
        $data = array_filter($data, function ($value) {
            return $value !== null; // Filter out null values
        }); 

        $user->update([
            'name' => $data['name'],
            'email' => $data['email'],
            'team_id' => $data['team_id'] ?? null, // Handle if team is optional
            'parent_id' => $data['parent_id'] ?? null,
            'designation' => $data['designation'] ?? null, // Handle if designation is optional
            'image' => $data['image'] ?? $user->image, // Keep existing
        ]);

        if (!empty($data['password'])) {
            $user->update(['password' => Hash::make($data['password'])]);
        }

        if (!empty($data['roles'])) {
            $user->syncRoles($data['roles']);
        }

        return $user;
    }
}