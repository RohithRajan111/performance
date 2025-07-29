<?php

namespace App\Actions\Leave;

use App\Models\LeaveApplication;
use App\Notifications\LeaveRequestApproved;
use App\Notifications\LeaveRequestRejected;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

class UpdateLeave
{
    public function handle(LeaveApplication $leaveApplication, string $status): void
    {
        $leaveApplication->update(['status' => $status]);
        // Check if the columns exist before trying to update them
        $updateData = ['status' => $status];

        if (Schema::hasColumn('leave_applications', 'approved_by')) {
            $updateData['approved_by'] = Auth::id();
        }

        if (Schema::hasColumn('leave_applications', 'approved_at')) {
            $updateData['approved_at'] = now();
        }

        $leaveApplication->update($updateData);

        // Send notification based on status
        if ($status === 'approved') {
            $leaveApplication->user->notify(new LeaveRequestApproved($leaveApplication));
        } elseif ($status === 'rejected') {
            $leaveApplication->user->notify(new LeaveRequestRejected($leaveApplication));
        }
    }
}
