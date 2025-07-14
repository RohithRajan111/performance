<?php
namespace App\Actions\Leave;

use Illuminate\Support\Facades\Auth;

class StoreLeave
{
    public function handle(array $data): void
    {
        Auth::user()->leaveApplications()->create($data);
    }
}
