<?php

namespace App\Actions\Leave;

use App\Models\LeaveApplication;
use App\Notifications\LeaveRequestSubmitted;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class StoreLeave
{
    public function handle(array $data): LeaveApplication
    {
        // Remember to REMOVE the dd($data) after you've seen the output!

        $user = Auth::user();

        $start = Carbon::parse($data['start_date']);
        $end = Carbon::parse($data['end_date']);
        $startSession = $data['start_half_session'] ?? null;
        $endSession = $data['end_half_session'] ?? null;

        if ($start->gt($end)) {
            throw ValidationException::withMessages(['start_date' => ['Start date must be before or equal to the end date.']]);
        }

        // =================================================================
        // --- FINAL, EXPLICIT & BULLETPROOF LEAVE DAYS CALCULATION ---
        // =================================================================
        $leaveDays = 0;
        $isSingleDay = $start->isSameDay($end);

        if ($isSingleDay) {
            $isFullDay = ($startSession === 'morning' && $endSession === 'afternoon');
            $isHalfDay =
                ($startSession === 'morning' && empty($endSession)) ||
                (empty($startSession) && $endSession === 'afternoon') ||
                ($startSession === 'morning' && $endSession === 'morning') || // Cover all edge cases
                ($startSession === 'afternoon' && $endSession === 'afternoon');

            if ($isFullDay) {
                // Case 1: Explicitly a full day (e.g., Morning to Afternoon)
                $leaveDays = 1.0;
            } elseif ($isHalfDay) {
                // Case 2: Explicitly a half day (e.g., only Morning is selected)
                $leaveDays = 0.5;
            } else {
                // Case 3: Default to a full day if no sessions or an invalid combo is provided.
                $leaveDays = 1.0;
            }
        } else {
            // Multi-day logic (this is generally correct)
            $firstDayValue = ($startSession === 'afternoon') ? 0.5 : 1.0;
            $lastDayValue = ($endSession === 'morning') ? 0.5 : 1.0;
            $daysInBetween = max(0, $start->diffInDays($end) - 1);
            $leaveDays = $firstDayValue + $lastDayValue + $daysInBetween;
        }


        // ... Your other validation and checks ...
        if ($leaveDays > $user->getRemainingLeaveBalance()) {
            throw ValidationException::withMessages([
                'leave_days' => ["You do not have enough leave balance. Remaining: {$user->getRemainingLeaveBalance()} days."],
            ]);
        }


        $leaveApplication = LeaveApplication::create([
            'user_id' => $user->id,
            'start_date' => $start,
            'end_date' => $end,
            'start_half_session' => $startSession,
            'end_half_session' => $endSession,
            'reason' => $data['reason'],
            'leave_type' => $data['leave_type'],
            'leave_days' => $leaveDays,
            'salary_deduction_days' => 0,
            'status' => 'pending',
        ]);

        $this->sendNotifications($leaveApplication);

        return $leaveApplication;
    }

    private function sendNotifications(LeaveApplication $leaveApplication): void
    {
        // This method is fine
        try {
            $approvers = $leaveApplication->user->getLeaveApprovers();
            if ($approvers->count() > 0) {
                foreach ($approvers as $approver) {
                    $approver->notify(new LeaveRequestSubmitted($leaveApplication));
                }
            }
        } catch (\Exception $e) {
            \Log::error('Failed to send leave request notifications', ['error' => $e->getMessage(), 'leave_id' => $leaveApplication->id]);
        }
    }
}