<?php
namespace App\Http\Controllers;

use App\Actions\Leave\GetLeave;
use App\Actions\Leave\StoreLeave;
use App\Actions\Leave\UpdateLeave;
use App\Http\Requests\Leave\StoreLeaveRequest;
use App\Http\Requests\Leave\UpdateLeaveRequest;
use App\Models\LeaveApplication;
use Inertia\Inertia;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Auth;

class LeaveApplicationController extends Controller
{
   public function index(GetLeave $getLeaveRequests)
{
    return Inertia::render('Leave/Index', $getLeaveRequests->handle());
}

   public function store(StoreLeaveRequest $request, StoreLeave $storeLeave)
{
    $storeLeave->handle($request->validated());

    return Redirect::route('leave.index')->with('success', 'Leave application submitted.');
}

    public function update(UpdateLeaveRequest $request, LeaveApplication $leave_application, UpdateLeave $updateLeaveStatus)
{
    $updateLeaveStatus->handle($leave_application, $request->validated()['status']);

    return Redirect::back()->with('success', 'Application status updated.');
}
}
