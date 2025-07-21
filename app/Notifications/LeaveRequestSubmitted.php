<?php
// app/Notifications/LeaveRequestSubmitted.php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class LeaveRequestSubmitted extends Notification
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
            'title' => 'New Leave Request',
            'message' => "Leave request from {$this->leaveRequest->user->name} requires approval",
            'type' => 'leave_request',
            'leave_id' => $this->leaveRequest->id,
            'user_name' => $this->leaveRequest->user->name,
            'start_date' => $this->leaveRequest->start_date,
            'end_date' => $this->leaveRequest->end_date,
            'url' => route('leave.index'), // Changed from leaves.show
        ];
    }
}
