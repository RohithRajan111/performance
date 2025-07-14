<?php

namespace App\Actions\User;

use App\Models\User;

class GetUsers
{
    public function handle()
    {
        return [
            'users' => User::with('roles:id,name')->latest()->paginate(10),
        ];
    }
}
