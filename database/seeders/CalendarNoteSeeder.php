<?php

namespace Database\Seeders;

use App\Models\CalendarNote;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class CalendarNoteSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::whereIn('email', ['employee@example.com', 'lead@example.com'])->get();
        if ($users->isEmpty()) {
            return;
        }

        CalendarNote::factory()->create([
            'user_id' => $users->first()->id,
            'date' => now()->addDays(3),
            'note' => 'Project Alpha planning session.',
        ]);

        CalendarNote::factory()->create([
            'user_id' => $users->last()->id,
            'date' => now()->addDays(10),
            'note' => 'Quarterly review meeting.',
        ]);
    }
}