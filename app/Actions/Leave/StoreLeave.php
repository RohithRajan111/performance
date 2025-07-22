<?php
// app/Actions/Leave/StoreLeave.php

namespace App\Actions\Leave;

use App\Models\LeaveApplication;
use Carbon\Carbon;
use App\Notifications\LeaveRequestSubmitted;
use Illuminate\Support\Facades\Auth;

class StoreLeave
{
    public function handle(array $data): LeaveApplication
    {
        $user = Auth::user();

        // Calculate requested days
        $start = Carbon::parse($data['start_date']);
        $end = Carbon::parse($data['end_date']);
        $requestedDays = $start->diffInDays($end) + 1;

        // Validate date range
        if ($start->gt($end)) {
            throw new \Exception('Start date must be before or equal to end date.');
        }

        if ($start->lt(Carbon::today())) {
            throw new \Exception('Cannot apply for leave in the past.');
        }

        // Check leave balance using model method
        $remainingBalance = $user->getRemainingLeaveBalance();
        if ($remainingBalance < $requestedDays) {
            throw new \Exception("Insufficient leave balance. You have {$remainingBalance} days remaining, but requested {$requestedDays} days.");
        }

        // Check for overlapping leave applications
        $overlapping = $user->leaveApplications()
            ->where('status', '!=', 'rejected')
            ->where(function ($query) use ($start, $end) {
                $query->whereBetween('start_date', [$start, $end])
                      ->orWhereBetween('end_date', [$start, $end])
                      ->orWhere(function ($q) use ($start, $end) {
                          $q->where('start_date', '<=', $start)
                            ->where('end_date', '>=', $end);
                      });
            })
            ->exists();

        if ($overlapping) {
            throw new \Exception('You already have a leave application for this date range.');
        }

        // Create the leave application with proper leave_type handling
        $leaveApplication = LeaveApplication::create([
            'user_id' => $user->id,
            'start_date' => $start,
            'end_date' => $end,
            'reason' => $data['reason'],
            'leave_type' => $data['leave_type'] ?? 'annual', // Provide default value
            'status' => 'pending',
        ]);

        // Send notifications to approvers
        $this->sendNotifications($leaveApplication);

        return $leaveApplication;
    }

    private function sendNotifications(LeaveApplication $leaveApplication): void
    {
        try {
            $approvers = $leaveApplication->user->getLeaveApprovers();
            
            if ($approvers->count() > 0) {
                foreach ($approvers as $approver) {
                    $approver->notify(new LeaveRequestSubmitted($leaveApplication));
                }
                
                \Log::info("Leave request notifications sent", [
                    'leave_id' => $leaveApplication->id,
                    'approvers_count' => $approvers->count()
                ]);
            } else {
                \Log::warning('No approvers found for leave request', [
                    'leave_id' => $leaveApplication->id
                ]);
            }
            
        } catch (\Exception $e) {
            \Log::error('Failed to send leave request notifications', [
                'error' => $e->getMessage(),
                'leave_id' => $leaveApplication->id
            ]);
        }
    }
}
