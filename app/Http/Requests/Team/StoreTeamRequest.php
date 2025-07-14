<?php

namespace App\Http\Requests\Team;

use App\Models\Team;
use Illuminate\Foundation\Http\FormRequest;

class StoreTeamRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255|unique:teams,name',
            'team_lead_id' => [
                'required',
                'exists:users,id',
                function ($value, $fail) {
                    if (Team::where('team_lead_id', $value)->exists()) {
                        $fail('This user is already leading another team.');
                    }
                },
            ],
        ];
    }
}
