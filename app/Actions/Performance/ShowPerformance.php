<?php
// app/Actions/Performance/ShowPerformance.php

namespace App\Actions\Performance;

use App\Models\LeaveApplication;
use App\Models\Task;
use App\Models\TimeLog;
use App\Models\User;
use Carbon\Carbon;

class ShowPerformance
{
    public function handle(User $user): array
    {
        $tasks = Task::where('assigned_to_id', $user->id);
        
        // Task statistics
        $taskStats = [
            'total' => $tasks->count(),
            'completed' => (clone $tasks)->where('status', 'completed')->count(),
            'in_progress' => (clone $tasks)->where('status', 'in_progress')->count(),
            'pending' => (clone $tasks)->where('status', 'pending')->count(),
        ];
        
        $taskStats['completion_rate'] = $taskStats['total'] > 0 ? 
            round(($taskStats['completed'] / $taskStats['total']) * 100, 1) : 0;

        // Time statistics
        $timeStats = [
            'total_hours' => TimeLog::where('user_id', $user->id)->sum('hours_worked') ?? 0,
            'current_month' => TimeLog::where('user_id', $user->id)
                ->whereMonth('work_date', Carbon::now()->month)
                ->whereYear('work_date', Carbon::now()->year)
                ->sum('hours_worked') ?? 0,
            'last_month' => TimeLog::where('user_id', $user->id)
                ->whereMonth('work_date', Carbon::now()->subMonth()->month)
                ->whereYear('work_date', Carbon::now()->subMonth()->year)
                ->sum('hours_worked') ?? 0,
        ];

        // Weekly hours for last 4 weeks
        $weeklyHours = collect();
        for ($i = 3; $i >= 0; $i--) {
            $weekStart = Carbon::now()->subWeeks($i)->startOfWeek(Carbon::MONDAY);
            $weekEnd = Carbon::now()->subWeeks($i)->endOfWeek(Carbon::SUNDAY);
            
            $hours = TimeLog::where('user_id', $user->id)
                ->whereBetween('work_date', [$weekStart->format('Y-m-d'), $weekEnd->format('Y-m-d')])
                ->sum('hours_worked') ?? 0;
                
            $weeklyHours->push([
                'week' => $weekStart->format('M j') . '-' . $weekEnd->format('j'),
                'hours' => (float) $hours,
            ]);
        }

        // Project hours
        $projectHours = TimeLog::where('user_id', $user->id)
            ->with(['project:id,name'])
            ->selectRaw('project_id, SUM(hours_worked) as total_hours')
            ->groupBy('project_id')
            ->get()
            ->map(function ($item) {
                return [
                    'project' => $item->project->name ?? 'No Project',
                    'hours' => (float) $item->total_hours,
                ];
            });

        // Leave statistics
        $approvedLeave = LeaveApplication::where('user_id', $user->id)
            ->where('status', 'approved')
            ->get();

        $leaveStats = [
            'total_days' => $approvedLeave->sum(fn ($leave) => 
                Carbon::parse($leave->start_date)->diffInDays(Carbon::parse($leave->end_date)) + 1
            ),
            'current_year' => $approvedLeave->filter(fn ($leave) => 
                Carbon::parse($leave->start_date)->year === Carbon::now()->year
            )->sum(fn ($leave) => 
                Carbon::parse($leave->start_date)->diffInDays(Carbon::parse($leave->end_date)) + 1
            ),
        ];
        
        $leaveStats['remaining'] = max(0, 12 - $leaveStats['current_year']);

        // Recent activities
        $recentTimeLogs = TimeLog::where('user_id', $user->id)
            ->with(['project:id,name'])
            ->orderBy('work_date', 'desc')
            ->limit(5)
            ->get(['project_id', 'work_date', 'hours_worked', 'description'])
            ->map(function ($log) {
                return [
                    'date' => Carbon::parse($log->work_date)->format('M d'),
                    'project' => $log->project->name ?? 'No Project',
                    'hours' => $log->hours_worked,
                    'description' => $log->description ?? 'No description',
                ];
            });

        $recentTasks = Task::where('assigned_to_id', $user->id)
            ->with(['project:id,name'])
            ->orderBy('updated_at', 'desc')
            ->limit(5)
            ->get(['id', 'name', 'status', 'project_id', 'updated_at'])
            ->map(function ($task) {
                return [
                    'name' => $task->name,
                    'project' => $task->project->name ?? 'No Project',
                    'status' => $task->status,
                    'updated' => Carbon::parse($task->updated_at)->diffForHumans(),
                ];
            });

        return [
            'employee' => $user->only('id', 'name', 'email'),
            'taskStats' => $taskStats,
            'timeStats' => $timeStats,
            'weeklyHours' => $weeklyHours->toArray(),
            'projectHours' => $projectHours->toArray(),
            'leaveStats' => $leaveStats,
            'recentTimeLogs' => $recentTimeLogs->toArray(),
            'recentTasks' => $recentTasks->toArray(),
        ];
    }
}
