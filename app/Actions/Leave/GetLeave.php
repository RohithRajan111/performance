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
    private function getLeaveColorCategory(
        LeaveApplication $request, 
        array $userBalances = [], 
        $currentUser = null, 
        bool $canManage = false
    ): string {
        // Prioritize status-based categories
        if ($request->status === 'pending') {
            return 'pending';
        }

        $leaveType = $request->leave_type;
        
        // Get remaining balance efficiently
        if ($request->user_id === optional($currentUser)->id) {
            // Use current user's cached balance
            $remainingBalance = $currentUser->getRemainingLeaveBalance();
        } elseif ($canManage && isset($userBalances[$request->user_id])) {
            // Use pre-calculated balance for other users
            $remainingBalance = $userBalances[$request->user_id];
        } elseif ($request->user && $request->user->leave_balance !== null) {
            // Use user's leave_balance field if available
            $remainingBalance = $request->user->leave_balance;
        } else {
            // Fallback to default
            $remainingBalance = 20;
        }

        // Determine color category based on leave type
        switch ($leaveType) {
            case 'personal':
                return $remainingBalance >= $request->leave_days ? 'personal' : 'paid';
            case 'annual':
                return 'annual';
            case 'sick':
                return 'sick';
            case 'emergency':
                return 'emergency';
            case 'maternity':
            case 'paternity':
                return $leaveType;
            default:
                return 'unknown';
        }
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

        if ($canManage) {
            // For managers, load all leave applications with user details
            $requests = LeaveApplication::with([
                'user:id,name,leave_balance',
                'approvedBy:id,name'
            ])
                ->orderByStatusPriority()
                ->latest()
                ->get();
        } else {
            // For regular users, only load their own applications
            $requests = LeaveApplication::forUser($user->id)
                ->orderByStatusPriority()
                ->latest()
                ->get();
        }

        // Get user's leave statistics in a single query
        $leaveStats = $user->getLeaveStatistics();

        // Pre-calculate leave balances for all users if manager
        $userBalances = [];
        if ($canManage) {
            $userIds = $requests->pluck('user_id')->unique();
            foreach ($userIds as $userId) {
                if ($userId !== $user->id) {
                    $userModel = $requests->where('user_id', $userId)->first()->user ?? null;
                    if ($userModel) {
                        $userBalances[$userId] = $userModel->getRemainingLeaveBalance();
                    }
                }
            }
        }

        // Optimize highlighted dates calculation
        $highlighted = $requests
            ->whereIn('status', ['pending', 'approved'])
            ->map(function ($request) use ($userBalances, $user, $canManage) {
                return [
                    'start' => $request->start_date->toDateString(),
                    'end' => $request->end_date ? $request->end_date->toDateString() : null,
                    'title' => $request->getLeaveTypeDisplayName(),
                    'class' => $request->status,
                    'color_category' => $this->getLeaveColorCategory($request, $userBalances, $user, $canManage),
                ];
            })
            ->values()
            ->all();

        return [
            'leaveRequests' => $requests,
            'canManage' => $canManage,
            'highlightedDates' => $highlighted,
            'remainingLeaveBalance' => $leaveStats['remaining_balance'],
            'leaveStatistics' => $leaveStats,
        ];
    }
}