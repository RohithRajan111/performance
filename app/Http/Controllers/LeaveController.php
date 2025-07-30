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
            // Eager-load the 'user' relationship to prevent performance issues (N+1 problem).
            // We only select the columns needed by the Vue component for efficiency.
            ->with('user:id,name,email')
            // Order the results with the most recent leave dates first.
            ->orderBy('start_date', 'desc');

        // 4. Apply the correct data scope based on the user's role.
        if ($canManage) {
            // A manager can see all leave requests.
            // No additional filtering is needed on the query.
            // If you wanted them to only see their team, you would add that logic here.
        } else {
            // A regular employee can only see their own leave requests.
            // This is a critical security/privacy filter.
            $query->where('user_id', $user->id);
        }

        // 5. Execute the query and paginate the results.
        //    This prevents loading thousands of records at once, ensuring the page is fast.
        $leaveRequests = $query->paginate(15); // You can adjust the number per page

        // 6. Render the Inertia Vue component and pass the data as props.
        //    The prop names here ('leaveRequests', 'canManage') must match the
        //    defineProps in your LeaveLogs.vue file.
        return Inertia::render('Leave/LeaveLogs', [
            'leaveRequests' => $leaveRequests,
            'canManage' => $canManage,
        ]);
    }
}
