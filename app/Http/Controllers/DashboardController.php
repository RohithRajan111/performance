<?php

namespace App\Http\Controllers;

use App\Models\CalendarNote;
use App\Models\LeaveApplication;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Illuminate\Support\Collection;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user()->load('parent'); // Eager load the 'parent' relationship

        // --- ATTENDANCE & GREETING DATA (No changes needed here) ---
        $totalEmployees = User::count();
        $absentTodayUsers = User::whereHas('leaveApplications', function ($query) {
            $today = now()->toDateString();
            $query->where('status', 'approved')->where('start_date', '<=', $today)->where('end_date', '>=', $today);
        })->get();

        $attendanceData = [
            'total' => $totalEmployees,
            'present' => $totalEmployees - $absentTodayUsers->count(),
            'absent' => $absentTodayUsers->count(),
            'absent_list' => $absentTodayUsers->map->only('id', 'name', 'designation', 'avatar_url'),
        ];

        $hour = now()->hour;
        $greetingMessage = 'Morning'; $greetingIcon = '🌤️';
        if ($hour >= 12 && $hour < 17) { $greetingMessage = 'Afternoon'; $greetingIcon = '☀️'; }
        elseif ($hour >= 17) { $greetingMessage = 'Evening'; $greetingIcon = '🌙'; }

        // --- CALENDAR DATA (No changes needed here) ---
        $leaveEvents = LeaveApplication::where('user_id', $user->id)->where('status', 'approved')->get()->map(fn($l) => ['id' => 'l'.$l->id, 'title' => 'Leave', 'start' => $l->start_date, 'end' => Carbon::parse($l->end_date)->addDay()->toDateString(), 'color' => '#EF4444']);
        $noteEvents = CalendarNote::where('user_id', $user->id)->get()->map(fn($n) => ['id' => 'n'.$n->id, 'title' => $n->note, 'start' => $n->date, 'color' => '#FBBF24']);
        $allCalendarEvents = (new Collection($leaveEvents))->merge($noteEvents);

        // --- **THE FIX IS HERE**: FETCHING PROJECTS AND TASKS ---

        // 1. Initialize empty collections. This is a safeguard.
        $projects = collect();
        $myTasks = collect();

        // 2. Fetch projects based on the user's role.
        if ($user->hasRole(['admin', 'project-manager'])) {
            // Admins and PMs see all active projects.
            $projects = Project::where('status', '!=', 'completed')->latest()->get();
        }
        elseif ($user->hasRole('team-lead')) {
            // Team Leads see only the active projects they are members of.
            // This requires the members() relationship in your Project model.
            $projects = Project::where('status', '!=', 'completed')
                ->whereHas('members', function ($query) use ($user) {
                    $query->where('user_id', $user->id);
                })
                ->latest()
                ->get();
        }

        // 3. Fetch tasks assigned directly to the current user.
        $myTasks = Task::where('assigned_to_id', $user->id)
            ->with('project:id,name') // Eager load project name for efficiency
            ->where('status', '!=', 'completed')
            ->orderBy('due_date', 'asc')
            ->get();


        // --- FINAL RENDER ---
        // Pass all the data, including the now-populated 'projects' and 'myTasks', to Vue.
        return Inertia::render('Dashboard', [
            'user' => $user->append('avatar_url'),
            'attendance' => $attendanceData,
            'calendarEvents' => $allCalendarEvents,
            'greeting' => [
                'message' => $greetingMessage,
                'icon' => $greetingIcon,
                'date' => now()->format('jS F Y'),
            ],
            // **CRUCIAL**: This sends the data to your `v-if` condition.
            'projects' => $projects,
            'myTasks' => $myTasks,
        ]);
    }
}