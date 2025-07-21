<?php
// app/Notifications/LeaveRequestRejected.php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class LeaveRequestRejected extends Notification
{
    use Queueable;

    protected $leaveRequest;

    public function __construct($leaveRequest)
    {
        $this->leaveRequest = $leaveRequest;
    }

    public function via($notifiable)
    {
        return ['database'];
    }

    public function toArray($notifiable)
    {
        return [
            'title' => 'Leave Request Rejected',
            'message' => 'Your leave request has been rejected',
            'type' => 'leave_rejected',
            'leave_id' => $this->leaveRequest->id,
            'start_date' => $this->leaveRequest->start_date,
            'end_date' => $this->leaveRequest->end_date,
            'rejected_by' => $this->leaveRequest->approvedByUser?->name ?? 'Admin',
            'url' => route('leave.index'), // Changed from leaves.show
        ];
    }
}
