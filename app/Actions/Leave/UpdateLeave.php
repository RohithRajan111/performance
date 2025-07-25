<?php

namespace App\Actions\Leave;

use App\Models\LeaveApplication;
use App\Notifications\LeaveRequestApproved;
use App\Notifications\LeaveRequestRejected;
use Illuminate\Support\Facades\Auth;

class UpdateLeave
{
    public function handle(LeaveApplication $leaveApplication, string $status): void
    {
        // Update the leave application record
        $leaveApplication->update([
            'status' => $status,
            'approved_by' => Auth::id(),
            'approved_at' => now(),
        ]);

        // --- DEFINITIVE FIX FOR TARGETED NOTIFICATIONS ---

        // 1. Get the user object of the person who originally created the leave application.
        $applicant = $leaveApplication->user;

        // 2. If we found the applicant, send them the correct notification.
        if ($applicant) {
            if ($status === 'approved') {
                $applicant->notify(new LeaveRequestApproved($leaveApplication));
            } elseif ($status === 'rejected') {
                $applicant->notify(new LeaveRequestRejected($leaveApplication));
            }
        }

        // --- END OF FIX ---
    }
}
