<?php

namespace Database\Seeders;

use App\Models\Project;
use App\Models\Task;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class TaskSeeder extends Seeder
{
    public function run(): void
    {
        $project = Project::where('name', 'Project Alpha')->first();
        if (!$project) {
            return; // Don't run if the project doesn't exist
        }

        $members = $project->members;
        if ($members->isEmpty()) {
            return;
        }

        $tasks = [
            'Create a dashboard' => 'in_progress',
            'Develop authentication module' => 'todo',
            'Design the UI/UX mockups' => 'completed',
            'Set up the database schema' => 'in_progress',
            'Write API documentation' => 'todo',
            'Implement user profile page' => 'todo',
            'Test payment gateway integration' => 'completed',
        ];

        foreach ($tasks as $name => $status) {
            Task::factory()->create([
                'name' => $name,
                'project_id' => $project->id,
                'assigned_to_id' => $members->random()->id,
                'status' => $status,
                'due_date' => now()->addDays(rand(5, 30)),
            ]);
        }
    }
}