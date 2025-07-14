<?php
namespace App\Providers;

use App\Models\LeaveApplication;
use App\Models\Project;
use App\Models\Task; // <-- IMPORT TASK MODEL
use App\Policies\ProjectPolicy;
use App\Policies\TaskPolicy; // <-- IMPORT TASK POLICY
use Illuminate\Support\Facades\Gate;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use App\Policies\LeaveApplicationPolicy;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        Project::class => ProjectPolicy::class,
        Task::class => TaskPolicy::class, // <-- ADD THIS LINE
        LeaveApplication::class => LeaveApplicationPolicy::class,
    ];

    public function boot(): void
    {
        Gate::before(function ($user, $ability) {
            return $user->hasRole('admin') ? true : null;
        });
    }
}