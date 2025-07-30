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
    \Log::info('Received leave request data', $data);

    $user = Auth::user();

    $start = Carbon::parse($data['start_date']);
    $end = Carbon::parse($data['end_date']);

    // Normalize half-day sessions to null if empty string or empty
    $startSession = $data['start_half_session'] ?? null;
    $endSession = $data['end_half_session'] ?? null;
    if ($startSession === '') $startSession = null;
    if ($endSession === '') $endSession = null;

    \Log::info('Normalized half sessions', compact('startSession', 'endSession'));

    if ($start->gt($end)) {
        throw ValidationException::withMessages([
            'start_date' => ['Start date must be before or equal to the end date.']
        ]);
    }

    // --- Leave days calculation ---
    $leaveDays = 0;
    $isSingleDay = $start->isSameDay($end);

    if ($isSingleDay) {
        $isFullDay = ($startSession === 'morning' && $endSession === 'afternoon');

        $isHalfDay =
            ($startSession === 'morning' && $endSession === null) ||
            ($startSession === null && $endSession === 'afternoon') ||
            ($startSession === 'morning' && $endSession === 'morning') ||
            ($startSession === 'afternoon' && $endSession === 'afternoon') ||
            ($startSession === 'afternoon' && $endSession === null); // <-- Added missing half-day case

        if ($isFullDay) {
            $leaveDays = 1.0;
        } elseif ($isHalfDay) {
            $leaveDays = 0.5;
        } else {
            $leaveDays = 1.0; // fallback full day
        }
    } else {
        // Multi-day leave calculation
        $firstDayValue = ($startSession === 'afternoon') ? 0.5 : 1.0;
        $lastDayValue = ($endSession === 'morning') ? 0.5 : 1.0;
        $daysInBetween = max(0, $start->diffInDays($end) - 1);
        $leaveDays = $firstDayValue + $lastDayValue + $daysInBetween;
    }

    $leaveDays = (float) $leaveDays; // cast to float explicitly

    // Validate leave balance
    // Validate leave balance except for emergency and sick leave
if (!in_array($data['leave_type'], ['emergency', 'sick']) && $leaveDays > $user->getRemainingLeaveBalance()) {
    throw ValidationException::withMessages([
        'leave_days' => ["You do not have enough leave balance. Remaining: {$user->getRemainingLeaveBalance()} days."],
    ]);
}


    // Handle supporting document upload
    if (isset($data['supporting_document'])) {
        $file = $data['supporting_document'];
        $path = $file->store('leave_documents/' . $user->id, 'public');
        $data['supporting_document_path'] = $path;
    }

    // Create leave application with all data
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
        'supporting_document_path' => $data['supporting_document_path'] ?? null,
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