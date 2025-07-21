<?php
// app/Notifications/LeaveRequestApproved.php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class LeaveRequestApproved extends Notification
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
            'title' => 'Leave Request Approved',
            'message' => 'Your leave request has been approved',
            'type' => 'leave_approved',
            'leave_id' => $this->leaveRequest->id,
            'start_date' => $this->leaveRequest->start_date,
            'end_date' => $this->leaveRequest->end_date,
            'approved_by' => $this->leaveRequest->approvedByUser?->name ?? 'Admin',
            'url' => route('leave.index'), // Changed from leaves.show
        ];
    }
}
