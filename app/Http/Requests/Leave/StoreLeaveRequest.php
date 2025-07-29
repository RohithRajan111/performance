<?php

namespace App\Http\Requests\Leave;

use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreLeaveRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'start_date' => ['required', 'date', 'after_or_equal:today', function ($attribute, $value, $fail) {
                $leaveType = $this->input('leave_type');
                $startDate = Carbon::parse($value);
                $today = Carbon::today();

                // Adjust the leave types and advance days as per your business logic
                if ($leaveType === 'annual') {
                    if ($startDate->lt($today->copy()->addDays(7))) {
                        $fail('Annual leave must be applied at least 7 days in advance.');
                    }
                } elseif ($leaveType === 'personal') {
                    if ($startDate->lt($today->copy()->addDays(3))) {
                        $fail('Personal leave must be applied at least 3 days in advance.');
                    }
                }
                // Add other leave type advance checks here if needed
            }],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'reason' => ['required', 'string', 'min:10'],
            'leave_type' => ['required', 'string', 'in:annual,sick,personal,emergency,maternity,paternity'],
            // Session fields for half-day leave
            'start_half_session' => ['nullable', 'in:morning,afternoon'],
            'end_half_session' => ['nullable', 'in:morning,afternoon'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function ($validator) {
            // Basic validation - detailed overlap check is handled in StoreLeave action
            // to avoid duplicate database queries and maintain consistency
            
            $startDate = Carbon::parse($this->input('start_date'));
            $endDate = Carbon::parse($this->input('end_date'));
            
            // Validate date range makes sense
            if ($startDate->gt($endDate)) {
                $validator->errors()->add('end_date', 'End date must be after or equal to start date.');
            }
            
            // Validate half-day session requirements
            $startSession = $this->input('start_half_session');
            $endSession = $this->input('end_half_session');
            
            if ($startSession || $endSession) {
                if ($startSession && !$endSession && !$startDate->isSameDay($endDate)) {
                    $validator->errors()->add('end_half_session', 'End session is required for multi-day half-day leave.');
                }
            }
        });
    }
}