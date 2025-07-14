<?php
namespace App\Http\Controllers;

use App\Models\LeaveApplication;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Auth;

class LeaveApplicationController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $requests = collect();

        if ($user->can('manage leave applications')) {
            // HR sees all requests, with user info, ordered by status
            $requests = LeaveApplication::with('user:id,name')
                ->orderByRaw("FIELD(status, 'pending', 'approved', 'rejected')")
                ->latest()->get();
        } else {
            // Employee sees only their own requests
            $requests = LeaveApplication::where('user_id', $user->id)
                ->latest()->get();
        }

        return Inertia::render('Leave/Index', [
            'leaveRequests' => $requests,
            'canManage' => $user->can('manage leave applications'),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'start_date' => 'required|date|after_or_equal:today',
            'end_date' => 'required|date|after_or_equal:start_date',
            'reason' => 'required|string|min:10',
        ]);

        Auth::user()->leaveApplications()->create($request->all());

        return Redirect::route('leave.index')->with('success', 'Leave application submitted.');
    }

    public function update(Request $request, LeaveApplication $leave_application)
    {
        $request->validate([
            'status' => 'required|string|in:approved,rejected',
        ]);

        $leave_application->update(['status' => $request->status]);

        return Redirect::back()->with('success', 'Application status updated.');
    }
}