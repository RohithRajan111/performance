<?php
namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Task;
use App\Models\TimeLog;
use App\Models\LeaveApplication;
use Carbon\Carbon;
use Inertia\Inertia;

class PerformanceReportController extends Controller
{
    public function show(User $user)
    {
        $tasks = Task::where('assigned_to_id', $user->id);
        $taskStats = [
            'total' => $tasks->count(),
            'completed' => (clone $tasks)->where('status', 'done')->count(),
        ];

        $totalHours = TimeLog::where('user_id', $user->id)->sum('hours_worked');
        
        $approvedLeave = LeaveApplication::where('user_id', $user->id)->where('status', 'approved')->get();
        $leaveDays = 0;
        foreach ($approvedLeave as $leave) {
            $leaveDays += Carbon::parse($leave->start_date)->diffInDays(Carbon::parse($leave->end_date)) + 1;
        }

        return Inertia::render('Performance/Show', [
            'employee' => $user->only('id', 'name', 'email'),
            'taskStats' => $taskStats,
            'totalHours' => $totalHours,
            'leaveDays' => $leaveDays,
        ]);
    }
}