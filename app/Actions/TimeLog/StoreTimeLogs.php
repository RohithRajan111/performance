<?php

namespace App\Actions\TimeLog;

use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class StoreTimeLogs
{
    /**
     * @throws ValidationException
     */
    public function handle(array $validatedData): void
    {
        $user = Auth::user();

        $workDate = Carbon::parse($validatedData['work_date']);

        // Create the time log entry
        $user->timeLogs()->create($validatedData);

        // Check if the work date is a weekend (Saturday or Sunday)
        if ($workDate->isWeekend()) {
            // Increment compensatory leave balance by 1 day (adjust if you use hours or partial days)
            $user->increment('comp_off_balance', 1);
        }
    }
}
