<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\LeaveApplication;
use Illuminate\Support\Facades\Auth;

class LeaveController extends Controller
{
    // ... your other existing controller methods like create(), store(), update(), etc. ...


    /**
     * Display a list of all relevant leave applications for the user.
     *
     * This method determines if the user is a manager or a regular employee.
     * Managers can see all leave requests, while employees can only see their own.
     * The data is paginated for performance and passed to the Inertia front-end.
     *
     * @param Request $request The incoming HTTP request.
     * @return \Inertia\Response
     */
    public function showLogs(Request $request)
    {
        // 1. Get the currently authenticated user.
        $user = Auth::user();

        // 2. Determine if the user has permission to manage leaves.
        //    This uses Laravel's built-in authorization and is the best practice.
        //    Make sure you have a Gate or Policy defined for 'manage leave applications'.
        $canManage = $user->can('manage leave applications');

        // 3. Start building the database query for Leave Applications.
        $query = LeaveApplication::query()

            ->with('user:id,name,email')

            ->orderBy('start_date', 'desc');

        if ($canManage) {

        } else {

            $query->where('user_id', $user->id);
        }

        $leaveRequests = $query->paginate(15);


        return Inertia::render('Leave/LeaveLogs', [
            'leaveRequests' => $leaveRequests,
            'canManage' => $canManage,
        ]);
    }
}
