<?php

namespace App\Http\Controllers;

use App\Models\CalendarNote;
use App\Models\LeaveApplication;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Illuminate\Support\Collection; // <-- Import the Collection class

class DashboardController extends Controller
{
    public function index()
    {
        $userId = Auth::id();
        $user = User::with('parent')->findOrFail($userId);

        // --- Data for Attendance Card (No changes needed here) ---
        $totalEmployees = User::count();
        $absentTodayUsers = User::whereHas('leaveApplications', function ($query) {
            $today = now()->toDateString();
            $query->where('status', 'approved')->where('start_date', '<=', $today)->where('end_date', '>=', $today);
        })->select('id', 'name', 'designation', 'avatar_url')->get();

        $attendanceData = [
            'total' => $totalEmployees,
            'present' => $totalEmployees - $absentTodayUsers->count(),
            'absent' => $absentTodayUsers->count(),
            'absent_list' => $absentTodayUsers,
        ];

        // --- THE DEFINITIVE FIX FOR THE MERGE ERROR ---

        // 1. Fetch Leave Events
        $leaveEvents = LeaveApplication::where('user_id', $userId)
            ->where('status', 'approved')
            ->get(['id', 'start_date', 'end_date', 'leave_type'])
            ->map(fn($leave) => [
                'id' => 'leave_' . $leave->id,
                'extendedProps' => ['type' => 'leave'],
                'title' => ucfirst($leave->leave_type) . ' Leave',
                'start' => $leave->start_date,
                'end' => Carbon::parse($leave->end_date)->addDay()->toDateString(),
                'allDay' => true,
                'color' => '#FECACA', 'textColor' => '#B91C1C'
            ]);

        // 2. Fetch Note Events
        $noteEvents = CalendarNote::where('user_id', $userId)
            ->get(['id', 'date', 'note'])
            ->map(fn($note) => [
                'id' => 'note_' . $note->id,
                'extendedProps' => ['note_id' => $note->id, 'type' => 'note'],
                'title' => $note->note,
                'start' => $note->date,
                'allDay' => true,
                'color' => '#FEF08A', 'textColor' => '#A16207'
            ]);

        // 3. Fetch Project Events
        $projectIds = Task::where('assigned_to_id', $userId)->distinct()->pluck('project_id');
        $projectEvents = Project::whereIn('id', $projectIds)
            ->whereNotNull('end_date')
            ->get(['id', 'name', 'end_date'])
            ->map(fn($project) => [
                'id' => 'project_' . $project->id,
                'extendedProps' => ['type' => 'project'],
                'title' => 'DUE: ' . $project->name,
                'start' => $project->end_date,
                'allDay' => true,
                'color' => '#C4B5FD', 'textColor' => '#5B21B6'
            ]);

        // 4. Safely combine all collections
        // We start with a base collection and merge the others into it.
        // This is more robust than chaining merges.
        $allCalendarEvents = new Collection($leaveEvents);
        $allCalendarEvents = $allCalendarEvents->merge($noteEvents);
        $allCalendarEvents = $allCalendarEvents->merge($projectEvents);

        return Inertia::render('Dashboard', [
            'user' => $user,
            'attendance' => $attendanceData,
            'calendarEvents' => $allCalendarEvents,
            'greeting' => [
                'time' => now()->format('h:i A'),
                'date' => now()->format('jS F Y'),
            ],
        ]);
    }
}