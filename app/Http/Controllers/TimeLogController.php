<?php
namespace App\Http\Controllers;

use App\Models\TimeLog;
use App\Models\Project;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;

class TimeLogController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $timeLogs = collect();
        $assignableProjects = collect();

        if ($user->can('view all working hours')) {
            // HR/Admin sees all logs, paginated
            $timeLogs = TimeLog::with(['user:id,name', 'project:id,name'])
                        ->latest('work_date')
                        ->latest('id') // Add secondary sort to see newest entries first
                        ->paginate(15);
        } else {
            // Employee sees their logs and projects they can log against
            $timeLogs = TimeLog::where('user_id', $user->id)
                                ->with('project:id,name')
                                ->latest('work_date')
                                ->latest('id')
                                ->paginate(15);
            
            // Find projects this employee is a part of via their team(s)
            $teamIds = $user->teams()->pluck('teams.id');
            $assignableProjects = Project::whereIn('team_id', $teamIds)->where('status', '!=', 'completed')->get(['id', 'name']);
        }

        return Inertia::render('Hours/Index', [
            'timeLogs' => $timeLogs,
            'canViewAll' => $user->can('view all working hours'),
            'assignableProjects' => $assignableProjects,
        ]);
    }

    public function store(Request $request)
{
    $request->validate([
        'project_id' => 'required|exists:projects,id',
        'work_date' => 'required|date|before_or_equal:today',
        'hours_worked' => 'required|numeric|min:0.25|max:24',
        'description' => 'nullable|string|max:1000',
    ]);

    Auth::user()->timeLogs()->create($request->all());

    return Redirect::route('hours.index')->with('success', 'Working hours logged successfully.');
}
}