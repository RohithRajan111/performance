<?php
// app/Http/Controllers/TaskController.php

namespace App\Http\Controllers;

use App\Actions\Task\StoreTask;
use App\Actions\Task\UpdateTaskStatus;
use App\Models\Task;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;

class TaskController extends Controller
{
    public function store(Request $request, Project $project, StoreTask $storeTask): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',  // Use 'name' instead of 'title'
            'assigned_to_id' => 'required|exists:users,id',
        ]);

        $storeTask->handle($project, $validated);

        return redirect()->back()->with('success', 'Task created successfully.');
    }

    public function update(Request $request, Task $task, UpdateTaskStatus $updateTaskStatus): RedirectResponse
    {
        // Handle status updates (from dashboard buttons)
        if ($request->has('status') && count($request->all()) == 1) {
            $validated = $request->validate([
                'status' => 'required|in:pending,in_progress,completed',
            ]);

            $updateTaskStatus->handle($task, $validated['status']);
        } else {
            // Handle full task updates
            $validated = $request->validate([
                'name' => 'sometimes|required|string|max:255',
                'assigned_to_id' => 'sometimes|required|exists:users,id',
                'status' => 'sometimes|required|in:pending,in_progress,completed',
            ]);

            $task->update($validated);
        }

        return redirect()->back()->with('success', 'Task updated successfully.');
    }
}
