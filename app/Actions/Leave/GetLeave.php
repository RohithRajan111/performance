<?php

namespace App\Actions\Leave;

use App\Models\LeaveApplication;
use Illuminate\Support\Facades\Auth;

class GetLeave
{
    /**
     * Determine the color category used for frontend display,
     * based on leave type and remaining leave balance.
     * Optimized to avoid repeated database queries.
     */
    private function getLeaveColorCategory(LeaveApplication $request): string
    {
        // Prioritize status-based categories
        if ($request->status === 'pending') {
            return 'pending'; // special category/color for pending
        }

        // Followed by leave type + paid logic as before
        $leaveType = $request->leave_type;
        $remainingBalance = $request->user->getRemainingLeaveBalance();

        if ($leaveType === 'personal') {
            if ($remainingBalance >= $request->leave_days) {
                return 'personal';
            } else {
                return 'paid';
            }
        }

        if ($leaveType === 'annual') {
            return 'annual';
        }
        if ($leaveType === 'sick') {
            return 'sick';
        }
        if ($leaveType === 'emergency') {
            return 'emergency';
        }
        if (in_array($leaveType, ['maternity', 'paternity'])) {
            return $leaveType;
        }

        return 'unknown';
    }

    /**
     * Fetch leave requests, annotate with color category,
     * and return data for frontend consumption.
     * Optimized to reduce database queries and improve performance.
     */
    public function handle(): array
    {
        $user = Auth::user();
        $canManage = $user->can('manage leave applications');

        if ($user->can('manage leave applications')) {
            $requests = LeaveApplication::with(['user:id,name'])
                ->orderByRaw("CASE status
                    WHEN 'pending' THEN 1
                    WHEN 'approved' THEN 2
                    WHEN 'rejected' THEN 3
                    ELSE 4
                    END")
                ->latest()
                ->get();
        } else {
            // For regular users, only load their own applications
            $requests = LeaveApplication::forUser($user->id)
                ->orderByStatusPriority()
                ->latest()
                ->get();
        }

        $highlighted = $requests->filter(fn ($request) => in_array($request->status, ['pending', 'approved']))
            ->map(fn ($request) => [
                'start' => $request->start_date->toDateString(),
                'end' => $request->end_date ? $request->end_date->toDateString() : null,
                'title' => ucfirst($request->leave_type).' Leave',
                'class' => $request->status,
                'color_category' => $this->getLeaveColorCategory($request),
                'user_id' => $request->user_id,
            ])->values()->all();

        return [
            'leaveRequests' => $requests,
            'canManage' => $canManage,
            'highlightedDates' => $highlighted,
            // 'remainingLeaveBalance' => $leaveStats['remaining_balance'],
            // 'leaveStatistics' => $leaveStats,
        ];
    }
}

