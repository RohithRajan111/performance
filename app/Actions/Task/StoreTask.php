<?php
// app/Actions/Task/StoreTask.php

namespace App\Actions\Task;

use App\Models\Project;

class StoreTask
{
    public function handle(Project $project, array $data)
    {
        return $project->tasks()->create([
            'name' => $data['name'],
            'assigned_to_id' => $data['assigned_to_id'],
            'status' => 'pending', // Use 'pending' instead of 'todo'
        ]);
    }
}
