<?php

namespace App\Http\Controllers;

use App\Actions\Leave\GetLeave;
use App\Actions\Leave\StoreLeave;
use App\Actions\Leave\UpdateLeave;
use App\Http\Requests\Leave\StoreLeaveRequest;
use App\Http\Requests\Leave\UpdateLeaveRequest;
use App\Models\LeaveApplication;
use App\Models\Team;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;

class LeaveApplicationController extends Controller
{
    public function index(GetLeave $getLeaveRequests)
    {
        return Inertia::render('Leave/Index', $getLeaveRequests->handle());
    }

    public function store(StoreLeaveRequest $request, StoreLeave $storeLeave)
    {
        $storeLeave->handle($request->validated());

        return redirect()->route('leave.index')->with('success', 'Leave application submitted.');
    }

    public function update(UpdateLeaveRequest $request, LeaveApplication $leave_application, UpdateLeave $updateLeaveStatus)
    {
        $updateLeaveStatus->handle($leave_application, $request->validated()['status']);

        return Redirect::back()->with('success', 'Application status updated.');
    }



    public function cancel(LeaveApplication $leave_application)
    {
        if ($leave_application->user_id !== Auth::id() || $leave_application->status !== 'pending') {
            abort(403, 'Unauthorized action.');
        }

        $leave_application->delete();

        return Redirect::route('leave.index')->with('success', 'Leave request canceled.');
    }
}