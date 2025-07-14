<?php

namespace App\Actions\Task;

use App\Models\Task;

class UpdateTaskStatus
{
    public function handle(Task $task, string $status)
    {
        $task->update([
            'status' => $status,
        ]);
    }
}
