<?php
// app/Actions/Leave/StoreLeave.php

namespace App\Actions\Leave;

use App\Models\User;
use App\Notifications\LeaveRequestSubmitted;
use Illuminate\Support\Facades\Auth;

class StoreLeave
{
    public function handle(array $data): void
    {
        $leaveApplication = Auth::user()->leaveApplications()->create($data);

        // Notify managers/admins about new leave request
        $managers = User::whereHas('roles', function ($query) {
            $query->whereIn('name', ['admin', 'project-manager', 'hr']); // Adjust role names as needed
        })->get();
        
        // Alternative: If you use permissions instead of roles
        // $managers = User::whereHas('permissions', function ($query) {
        //     $query->where('name', 'manage leave applications');
        // })->get();
        
        foreach ($managers as $manager) {
            $manager->notify(new LeaveRequestSubmitted($leaveApplication));
        }
    }
}
