<?php

namespace App\Http\Controllers;

use App\Models\Team;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Inertia\Inertia;

class LeaveCalendarController extends Controller
{
    public function index(Request $request)
    {
        // 1. Get raw filters from request
        $employee_name = $request->get('employee_name');
        $team_id = $request->get('team_id');
        $start_date = $request->get('start_date', now()->startOfMonth()->format('Y-m-d'));
        $end_date = $request->get('end_date', now()->endOfMonth()->format('Y-m-d'));
        $show_absent_only = $request->boolean('show_absent_only');

        $startDate = Carbon::parse($start_date);
        $endDate = Carbon::parse($end_date);

        // 2. Build users query with optimized leave applications loading
        $usersQuery = User::query()
            ->with([
                'leaveApplications' => function ($query) use ($startDate, $endDate) {
                    $query->approved()
                        ->overlapsWith($startDate, $endDate)
                        ->select('id', 'user_id', 'start_date', 'end_date', 'leave_type', 'day_type', 'leave_days');
                },
                'teams:id,name',
            ])
            ->select('id', 'name')
            ->orderBy('name');

        // Apply employee name filter
        if (! empty($employee_name)) {
            $usersQuery->where('name', 'like', "%{$employee_name}%");
        }

        // Apply team filter
        if (! empty($team_id)) {
            $usersQuery->whereHas('teams', function ($teamQuery) use ($team_id) {
                $teamQuery->where('teams.id', $team_id);
            });
        }

        $users = $usersQuery->get();

        // 3. Generate date range
        $period = CarbonPeriod::create($startDate, $endDate);
        $dateRange = collect($period)->map(fn ($date) => $date->format('Y-m-d'));

        // 4. Process calendar data
        $calendarData = $users->map(function ($user) use ($dateRange) {
            $dailyStatuses = [];
            $hasAbsence = false;
            $today = Carbon::today(); // Get today's date for comparison

            foreach ($dateRange as $dateString) {
                $date = Carbon::parse($dateString);
                $status = 'Working';
                $details = null;

                $onLeave = $user->leaveApplications->first(function ($leave) use ($date) {
                    return $date->between($leave->start_date, $leave->end_date);
                });

                if ($onLeave) {
                    $hasAbsence = true;
                    $status = 'Leave';
                    $details = [
                        'code' => strtoupper(substr($onLeave->leave_type, 0, 1)),
                        'color' => $this->getLeaveTypeColor($onLeave->leave_type),
                        'leave_type' => ucfirst($onLeave->leave_type),
                        'day_type' => $onLeave->day_type,
                    ];
                } elseif ($date->isWeekend()) {
                    $status = 'Weekend';
                } elseif ($date->isAfter($today)) {
                    // Future dates - not yet occurred
                    $status = 'Future';
                }
                // If none of the above conditions are met, it remains 'Working' (present for past/today dates)

                $dailyStatuses[$dateString] = ['status' => $status, 'details' => $details];
            }

            return [
                'id' => $user->id,
                'name' => $user->name,
                'teams' => $user->teams->pluck('name')->join(', '),
                'daily_statuses' => $dailyStatuses,
                'has_absence' => $hasAbsence,
            ];
        });

        // 5. Apply absent only filter
        if ($show_absent_only) {
            $calendarData = $calendarData->filter(function ($user) {
                return $user['has_absence'] === true;
            });
        }

        // 6. Prepare response data
        $filters = [
            'employee_name' => $employee_name ?? '',
            'team_id' => $team_id ?? '',
            'start_date' => $start_date,
            'end_date' => $end_date,
            'show_absent_only' => $show_absent_only,
        ];

        return Inertia::render('Leave/Calendar', [
            'calendarData' => $calendarData->values(),
            'dateRange' => $dateRange,
            'teams' => Team::orderBy('name')->get(['id', 'name']),
            'filters' => $filters,
        ]);
    }

    private function getLeaveTypeColor($leaveType)
    {
        $colors = [
            'sick' => '#ff9800',
            'annual' => '#EF5350',
            'personal' => '#9C27B0',
            'emergency' => '#F44336',
            'maternity' => '#E91E63',
            'paternity' => '#3F51B5',
        ];

        return $colors[$leaveType] ?? '#607D8B';
    }
}
