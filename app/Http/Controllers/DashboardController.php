<?php

namespace App\Http\Controllers;

use App\Models\CalendarNote;
use App\Models\LeaveApplication;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user()->load('parent');

        // --- ATTENDANCE & GREETING DATA ---
        $totalEmployees = User::count();
        $absentTodayUsers = User::whereHas('leaveApplications', function ($query) {
            $today = now()->toDateString();
            $query->where('status', 'approved')
                  ->where('start_date', '<=', $today)
                  ->where('end_date', '>=', $today);
        })->get();

        $attendanceData = [
            'total' => $totalEmployees,
            'present' => $totalEmployees - $absentTodayUsers->count(),
            'absent' => $absentTodayUsers->count(),
            'absent_list' => $absentTodayUsers->map->only('id', 'name', 'designation', 'avatar_url'),
        ];

        $hour = now()->hour;
        $greetingMessage = 'Morning';
        $greetingIcon = '🌤️';
        if ($hour >= 12 && $hour < 17) {
            $greetingMessage = 'Afternoon';
            $greetingIcon = '☀️';
        } elseif ($hour >= 17) {
            $greetingMessage = 'Evening';
            $greetingIcon = '🌙';
        }

        // --- CALENDAR DATA (FIXED) ---
        // The key changes are here to fix the time prefix issue
        $leaveEvents = LeaveApplication::where('user_id', $user->id)
            ->where('status', 'approved')
            ->get()
            ->map(function($leave) {
                return [
                    'id' => 'leave_' . $leave->id,
                    'title' => ucfirst($leave->leave_type) . ' Leave',
                    'start' => $leave->start_date, // Just date, no time
                    'end' => $leave->start_date === $leave->end_date
                        ? null // Single day event
                        : Carbon::parse($leave->end_date)->addDay()->toDateString(),
                    'allDay' => true, // This is crucial - makes it an all-day event
                    'backgroundColor' => $this->getLeaveColor($leave->leave_type),
                    'borderColor' => $this->getLeaveColor($leave->leave_type),
                    'textColor' => '#ffffff',
                    'extendedProps' => [
                        'type' => 'leave',
                        'leave_type' => $leave->leave_type,
                        'status' => $leave->status,
                        'day_type' => $leave->day_type ?? 'full_day',
                    ]
                ];
            });

        $noteEvents = CalendarNote::where('user_id', $user->id)
            ->get()
            ->map(function($note) {
                return [
                    'id' => 'note_' . $note->id,
                    'title' => $note->note,
                    'start' => $note->date,
                    'allDay' => true, // Notes are also all-day events
                    'backgroundColor' => '#FBBF24',
                    'borderColor' => '#F59E0B',
                    'textColor' => '#000000',
                    'extendedProps' => [
                        'type' => 'note',
                        'note_id' => $note->id,
                    ]
                ];
            });

        $allCalendarEvents = (new Collection($leaveEvents))->merge($noteEvents);

        // --- PROJECTS AND TASKS ---
        $projects = collect();
        $myTasks = collect();

        // Fetch projects based on user role
        if ($user->hasRole(['admin', 'project-manager'])) {
            $projects = Project::where('status', '!=', 'completed')
                ->latest()
                ->get();
        } elseif ($user->hasRole('team-lead')) {
            $projects = Project::where('status', '!=', 'completed')
                ->whereHas('members', function ($query) use ($user) {
                    $query->where('user_id', $user->id);
                })
                ->latest()
                ->get();
        }

        // Fetch tasks assigned to current user
        $myTasks = Task::where('assigned_to_id', $user->id)
            ->with('project:id,name')
            ->where('status', '!=', 'completed')
            ->orderBy('due_date', 'asc')
            ->get();

        return Inertia::render('Dashboard', [
            'user' => $user->append('avatar_url'),
            'attendance' => $attendanceData,
            'calendarEvents' => $allCalendarEvents,
            'greeting' => [
                'message' => $greetingMessage,
                'icon' => $greetingIcon,
                'date' => now()->format('jS F Y'),
            ],
            'projects' => $projects,
            'myTasks' => $myTasks,
        ]);
    }
}


    /**
     * Get color for different leave types
     */
    private function getLeaveColor($leaveType)
    {
        $colors = [
            'annual' => '#3B82F6',    // Blue
            'sick' => '#EF4444',      // Red
            'personal' => '#F59E0B',  // Amber
            'emergency' => '#DC2626', // Dark Red
            'maternity' => '#EC4899', // Pink
            'paternity' => '#8B5CF6', // Purple
        ];

        return $colors[$leaveType] ?? '#6B7280'; // Default gray
    }
}
