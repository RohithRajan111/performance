<?php

namespace App\Http\Requests\Team;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTeamRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Or add authorization logic
    }

    public function rules(): array
    {
        // $this->team is the Team model instance from the route model binding
        return [
            'name' => [
                'required',
                'string',
                'max:255',
                // Ensure the name is unique, but ignore the current team's ID
                Rule::unique('teams')->ignore($this->team->id),
            ],
            'team_lead_id' => ['required', 'exists:users,id'],
        ];
    }
}