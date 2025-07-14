<?php

namespace App\Actions\TimeLog;

use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class StoreTimeLogs
{
    /**
     * @throws ValidationException
     */
    public function handle(array $validatedData): void
    {
        Auth::user()->timeLogs()->create($validatedData);
    }
}
