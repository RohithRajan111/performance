<?php
namespace App\Http\Controllers;

use App\Actions\Performance\ShowPerformance;
use App\Models\User;
use App\Models\Task;
use App\Models\TimeLog;
use App\Models\LeaveApplication;
use Carbon\Carbon;
use Inertia\Inertia;

class PerformanceReportController extends Controller
{
    public function show(User $user, ShowPerformance $performance)
{
    return Inertia::render('Performance/Show', $performance->handle($user));
}
}
