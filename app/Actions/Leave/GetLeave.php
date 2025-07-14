<?php
namespace App\Actions\Leave;

use App\Models\LeaveApplication;
use Illuminate\Support\Facades\Auth;

class GetLeave
{
    public function handle(): array
    {
        $user = Auth::user();

        if ($user->can('manage leave applications')) {
            $requests = LeaveApplication::with('user:id,name')
                ->orderByRaw("FIELD(status, 'pending', 'approved', 'rejected')")
                ->latest()->get();
        } else {
            $requests = LeaveApplication::where('user_id', $user->id)
                ->latest()->get();
        }

        return [
            'leaveRequests' => $requests,
            'canManage' => $user->can('manage leave applications'),
        ];
    }
}

