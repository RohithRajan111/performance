<?php
namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class TaskController extends Controller
{
    use AuthorizesRequests;
    public function store(Request $request, Project $project)
    {
        // Authorize that the user can update the project (which implies they can add tasks)
        // You can create a new `update` method in ProjectPolicy if you want more granular control
        $this->authorize('view', $project);

        $request->validate([
            'name' => 'required|string|max:255',
            'assigned_to_id' => 'required|exists:users,id',
        ]);

        $project->tasks()->create([
            'name' => $request->name,
            'assigned_to_id' => $request->assigned_to_id,
            'status' => 'todo', // Default status
        ]);

        return Redirect::back()->with('success', 'Task created.');
    }
    public function update(Request $request, Task $task)
    {
        // This will use the TaskPolicy to ensure only the assigned user can update it.
        $this->authorize('update', $task);

        $request->validate([
            'status' => 'required|string|in:todo,in-progress,done',
        ]);

        $task->update([
            'status' => $request->status,
        ]);

        return Redirect::back()->with('success', 'Task status updated.');
    }
}