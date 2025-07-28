<?php

namespace App\Actions\Leave;

use App\Models\LeaveApplication;
use App\Notifications\LeaveRequestSubmitted;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class StoreLeave
{
    /**
     * Handles the creation of a new leave application.
     *
     * @param array $data The validated data from the request.
     * @return LeaveApplication
     * @throws ValidationException
     */
    public function handle(array $data): LeaveApplication
    {
        $user = Auth::user();
        info($data);
        $start = Carbon::parse($data['start_date']);
        $end = Carbon::parse($data['end_date']);
        $dayType = $data['day_type'] ?? 'full';

        // --- Basic Date Validations ---
        if ($start->gt($end)) {
            throw ValidationException::withMessages([
                'start_date' => ['Start date must be before or equal to the end date.'],
            ]);
        }

        if ($start->isPast() && !$start->isToday()) {
            throw ValidationException::withMessages([
                'start_date' => ['Cannot apply for leave in the past.'],
            ]);
        }

        // --- Leave Days Calculation ---
        $requestedDays = 0;
        if ($dayType === 'half') {
            // Validate that the required session fields are present
            if (empty($data['start_half_session'])) {
                throw ValidationException::withMessages(['start_half_session' => 'The start session is required for a half-day leave.']);
            }
            if ($start->ne($end) && empty($data['end_half_session'])) {
                throw ValidationException::withMessages(['end_half_session' => 'The end session is required for a multi-day half-day leave.']);
            }

            if ($start->isSameDay($end)) {
                // Simple case: A single half-day leave is always 0.5 days.
                $requestedDays = 0.5;
            } else {
                // Complex case: A date range involving half-days.
                $totalDays = $start->diffInDaysFiltered(fn ($date) => !$date->isWeekend(), $end) + 1;
                $deduction = 0;

                // If leave starts in the afternoon, the morning was worked (deduct 0.5)
                if ($data['start_half_session'] === 'afternoon') {
                    $deduction += 0.5;
                }
                // If leave ends in the morning, the afternoon will be worked (deduct 0.5)
                if ($data['end_half_session'] === 'morning') {
                    $deduction += 0.5;
                }
                $requestedDays = $totalDays - $deduction;
            }
        } else {
            // Full day calculation: Count all days in the range.
            $requestedDays = $start->diffInDaysFiltered(fn ($date) => !$date->isWeekend(), $end) + 1;
        }

        // Ensure the leave period is valid
        if ($requestedDays <= 0) {
             throw ValidationException::withMessages(['end_half_session' => 'The selected leave period results in zero or fewer leave days. Please check your start and end sessions.']);
        }


        // --- Leave Balance and Deduction Logic ---
        $leaveType = $data['leave_type'] ?? 'annual';
        $remainingBalance = $user->getRemainingLeaveBalance();
        $salaryDeductionDays = 0;
        $leaveToDeduct = 0;

        switch ($leaveType) {
            case 'annual':
                if ($remainingBalance < $requestedDays) {
                    throw ValidationException::withMessages([
                        'start_date' => ["Insufficient annual leave balance. You have {$remainingBalance} days remaining, but requested {$requestedDays} days."],
                    ]);
                }
                $leaveToDeduct = $requestedDays;
                break;

            case 'sick':
                // Assuming sick leave doesn't deduct from annual balance
                $leaveToDeduct = 0;
                break;

            case 'personal':
                if ($remainingBalance >= $requestedDays) {
                    $leaveToDeduct = $requestedDays;
                } else {
                    $leaveToDeduct = $remainingBalance;
                    $salaryDeductionDays = $requestedDays - $remainingBalance;
                }
                break;

            case 'maternity':
                $maxMaternityDays = 6 * 30; // approx 6 months
                if ($requestedDays > $maxMaternityDays) {
                    throw ValidationException::withMessages(['start_date' => ['Maternity leave cannot exceed 6 months.']]);
                }
                $leaveToDeduct = 0;
                break;

            case 'paternity':
                $maxPaternityDays = 2 * 30; // approx 2 months
                if ($requestedDays > $maxPaternityDays) {
                    throw ValidationException::withMessages(['start_date' => ['Paternity leave cannot exceed 2 months.']]);
                }
                $leaveToDeduct = 0;
                break;

            case 'emergency':
                if ($remainingBalance >= $requestedDays) {
                    $leaveToDeduct = $requestedDays;
                } else {
                    $leaveToDeduct = $remainingBalance;
                }
                break;

            default:
                throw ValidationException::withMessages(['leave_type' => ['Invalid leave type selected.']]);
        }

        // --- Overlapping Leave Check ---
        $overlapping = $user->leaveApplications()
            ->where('status', '!=', 'rejected')
            ->where(function ($q) use ($start, $end) {
                $q->whereBetween('start_date', [$start, $end])
                  ->orWhereBetween('end_date', [$start, $end])
                  ->orWhere(fn ($q2) => $q2->where('start_date', '<=', $start)->where('end_date', '>=', $end));
            })
            ->exists();

        if ($overlapping) {
            throw ValidationException::withMessages([
                'start_date' => ['You already have an approved or pending leave application that overlaps with these dates.'],
            ]);
        }

        // --- Create Leave Application in Database ---
        $leaveApplication = LeaveApplication::create([
            'user_id' => $user->id,
            'start_date' => $start,
            'end_date' => $end,
            'reason' => $data['reason'],
            'leave_type' => $leaveType,
            'day_type' => $dayType,
            'start_half_session' => $dayType === 'half' ? $data['start_half_session'] : null,
            'end_half_session' => ($dayType === 'half' && $start->ne($end)) ? $data['end_half_session'] : ($dayType === 'half' ? $data['start_half_session'] : null),
            'leave_days' => $requestedDays,
            'salary_deduction_days' => $salaryDeductionDays,
            'status' => 'pending',
        ]);

    $this->sendNotifications($leaveApplication);

    return $leaveApplication;
}

    /**
     * Sends notifications to the designated leave approvers.
     *
     * @param LeaveApplication $leaveApplication
     */
    private function sendNotifications(LeaveApplication $leaveApplication): void
    {
        try {
            $approvers = $leaveApplication->user->getLeaveApprovers();

            if ($approvers->count() > 0) {
                foreach ($approvers as $approver) {
                    $approver->notify(new LeaveRequestSubmitted($leaveApplication));
                }

                \Log::info('Leave request notifications sent', [
                    'leave_id' => $leaveApplication->id,
                    'approvers_count' => $approvers->count(),
                ]);
            } else {
                \Log::warning('No approvers found for leave request', [
                    'leave_id' => $leaveApplication->id,
                ]);
            }
        } catch (\Exception $e) {
            \Log::error('Failed to send leave request notifications', [
                'error' => $e->getMessage(),
                'leave_id' => $leaveApplication->id,
            ]);
        }
    }
}
