<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Or add authorization logic
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            // Ensure email is unique but ignore the current user
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($this->user->id)],
            // Make password optional for updates
            'password' => ['nullable', 'confirmed', Password::defaults()],
            'roles' => ['required', 'array'],
            'roles.*' => ['exists:roles,id'], // Check if the role ID exists
            'team_id' => ['nullable', 'exists:teams,id'],
        ];
    }
}