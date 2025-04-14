<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\Project;
use Illuminate\Http\Request;

class TaskController extends Controller
{
    public function index(Request $request) 
    {
        $user = auth()->user();
        $query = $request->query('query');

        // Get user's tasks with optional search filter
        $tasks = $user->tasks()
            ->when(!empty($query), function ($q) use ($query) {
                $q->where('agenda', 'like', '%' . $query . '%');
            })
            ->orderBy("created_at", "desc")
            ->paginate(20);

        return $this->sendResponse($tasks);
    }

    // List all tasks for a project (Team Lead and Members can view)
    public function olDindex(Project $project)
    {
        $this->authorize('view', [auth()->user(), $project]);

        $tasks = $project->tasks()->with(['assignedTo', 'status'])->get();

        return response()->json($tasks);
    }

    // Create a new task (Team Lead only)
    public function olDstore(Request $request, Project $project)
    {
        $this->authorize('create', [auth()->user(), $project]);

        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status_id' => 'required|exists:project_tasks_statuses,id',
            'assigned_to' => 'required|exists:users,id',
        ]);

        $task = $project->tasks()->create($request->only(['title', 'description', 'status_id', 'assigned_to']));

        return response()->json($task, 201);
    }

    // Update a task (Team Lead only)
    public function olDupdate(Request $request, Task $task)
    {
        $this->authorize('update', [auth()->user(), $task]);

        $request->validate([
            'title' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'status_id' => 'sometimes|exists:project_statuses,id',
            'assigned_to' => 'sometimes|exists:users,id',
        ]);

        $task->update($request->only(['title', 'description', 'status_id', 'assigned_to']));

        return response()->json($task);
    }

    // Delete a task (Team Lead only)
    public function olDdestroy(Task $task)
    {
        $this->authorize('create', [auth()->user(), $task]);

        $task->delete();

        return response()->json(['message' => 'Task deleted successfully']);
    }

    public function olDmarkAsCompleted(Task $task)
    {
        $this->authorize('update', [auth()->user(), $task]);
        $task->markAsCompleted();

        return response()->json(['message'=> 'Task completed successfully!']);
    }
}
