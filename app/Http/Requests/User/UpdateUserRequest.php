<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Or your authorization logic
    }

    public function rules(): array
    {
        // Get the user ID from the route to ignore their own email address
        $userId = $this->route('user')->id;

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($userId)],
            
            // ⭐️ KEY CHANGE: 'nullable' allows this field to be empty
            // If it's not empty, it must be a string, min 8 chars, and confirmed.
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],

            'role' => ['required', 'string', Rule::exists('roles', 'name')],
            
            // ⭐️ KEY CHANGE: 'nullable' allows this field to be empty
            'team_id' => ['nullable', 'integer', Rule::exists('teams', 'id')],
            'parent_id' => ['nullable', 'integer', Rule::exists('users', 'id')],

            // ⭐️ KEY CHANGE: 'nullable' allows the image to be optional
            'image' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif', 'max:2048'],
        ];
    }
}