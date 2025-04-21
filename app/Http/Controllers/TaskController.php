<?php

namespace App\Http\Controllers;

use App\Http\Resources\SingleTaskResource;
use App\Http\Resources\TaskCollectionResource;
use App\Models\Attachment;
use App\Models\Discussion;
use App\Models\Task;
use App\Models\Project;
use App\Notifications\AppNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Notification;
use function Illuminate\Support\defer;

class TaskController extends Controller
{
    public function index(Request $request)
    {
        $perPage = 10;
        $page = request('page', 1);
        $user = auth()->user();
        $query = $request->query('query');

        if ($user->hasAnyAppRole(['Admin', 'Team Lead', 'Super Admin'])) {
            $projectIds = Project::accessible($user)->pluck('id');
            $tasksQuery = Task::whereIn('project_id', $projectIds);
        } else {
            $tasksQuery = $user->tasks();
        }

        $tasks = $tasksQuery
            ->with(['assignedUser', 'project', 'priority', 'status'])
            ->when(!empty($query), function ($q) use ($query) {
                $q->where(function ($q) use ($query) {
                    $q->where('title', 'like', "%{$query}%")
                    ->orWhere('description', 'like', "%{$query}%");
                });
            })
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return $this->sendPaginatedResponse(TaskCollectionResource::collection($tasks), $page, $perPage);
    }

    public function show(Request $request, Task $task)
    {
        $user = auth()->user();

        $task->load([
            'assignedUser',
            'project.team.members',
            'priority',
            'discussions.user',
            'attachments.user',
            'status',
        ])->loadCount(['discussions', 'attachments']);

        // Permission check before returning task
        if (
            !$user->hasAnyAppRole(['Admin', 'Super Admin']) &&
            !$task->project->team->members->contains($user->id)
        ) {
            return $this->sendError('Unauthorized action!',[], 403);
        }

        return $this->sendResponse(SingleTaskResource::make($task));
    }

    // Create a new task (All team members)
    public function store(Request $request)
    {
        $project = Project::with('team.members')->find($request->project_id);
        if (!$project) {
            return $this->sendError('Project not found ' . $request->project_id, [], 404);
        }

        if ($project->is_completed) {
            return $this->sendError('Project already completed!', [], 403);
        }

        $user = auth()->user();

        if (!$user->hasAnyAppRole(['Admin', 'Super Admin']) && !$project->team->members->contains($user)) {
            return $this->sendError('Unauthorized action!', [], 403);
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status_id' => 'required|exists:project_task_statuses,id',
            'assigned_to' => 'required|exists:users,id',
            'priority_id' => 'required|exists:priority_statuses,id',
            'due_date' => 'required|date|after:today',
        ]);

        try {
            DB::beginTransaction();

            $task = $project->tasks()->create(array_merge(
                $validated,
                ['project_id' => $project->id]
            ));

            DB::commit();

            // Defer notification sending until after response is sent
            if ($task->assigned_to != $user->id) {
                defer(function () use ($task, $project) {
                    $task->load('priority', 'assignedUser'); // ensure relations are loaded

                    $taskData = [
                        'title' => $task->title,
                        'description' => $task->description,
                        'due_date' => $task->due_date,
                        'priority' => optional($task->priority)->name,
                    ];

                    Notification::send($task->assignedUser, new AppNotification(
                        'A new task was just assigned to you on ' . $project->name,
                        'tasks',
                        $task->id,
                        true,
                        $taskData
                    ));
                });
            }

            return $this->sendResponse(null, 'Task assigned successfully');
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Task assignment failed: ' . $e->getMessage(), ['trace' => $e->getTrace()]);
            return $this->sendError('An error occurred while assigning the task.', [], 500);
        }
    }

    public function update(Request $request, Task $task)
    {
        $user = auth()->user();

        $task->load('project.team');

        if (!$user->hasAnyAppRole(['Admin', 'Super Admin']) && !$task->project->team->team_lead_id != $user->id && $task->assigned_to != $user->id) {
            return $this->sendError('Unauthorized action!',[], 403);
        }

        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status_id' => 'required|exists:project_task_statuses,id',
            'assigned_to' => 'required|exists:users,id',
            'due_date' => 'required|date|after_or_equal:today',
            'priority_id' => 'required|exists:priority_statuses,id',
        ]);

        $project = Project::with('team.members')->find($request->project_id);
        if (!$project) {
            return $this->sendError('Project not found', 404);
        }

        if ($project->is_completed) {
            return $this->sendError('Project already completed!', [], 403);
        }

        $assignedChanged = $task->assigned_to !== $request->assigned_to;

        $task->update($request->only(['title', 'description', 'status_id', 'assigned_to', 'due_date', 'priority_id']));

        if ($assignedChanged) {
            defer(function () use ($task, $project) {
                $task->load('priority', 'assignedUser');

                $taskData = [
                    'title' => $task->title,
                    'description' => $task->description,
                    'due_date' => $task->due_date,
                    'priority' => optional($task->priority)->name,
                ];

                Notification::send($task->assignedUser, new AppNotification(
                    'A task was just reassigned to you on ' . $project->name,
                    'tasks',
                    $task->id,
                    true,
                    $taskData
                ));
            });
        }

        return $this->sendResponse(null, 'Task updated successfully');
    }


    // Delete a task (Team Lead only)
    public function destroy(Task $task)
    {
        $task->load('project');
        if ($task->project->is_completed) {
            return $this->sendError('Project already completed!', [], 403);
        }

        $user = auth()->user();
        if (!$user->hasAnyAppRole(['Admin', 'Team Lead', 'Super Admin'])) {
            return $this->sendError('Unauthorized action!',[], 403);
        }

        $task->delete();

        return $this->sendResponse(null, 'Task deleted successfully');
    }

    public function markAsCompleted(Task $task)
    {
        $user = auth()->user();
        if (!$user->hasAnyAppRole(['Admin', 'Team Lead', 'Super Admin'])) {
            return $this->sendError('Unauthorized action!',[], 403);
        }
        $task->markAsCompleted();

        return $this->sendResponse(null, 'Task completed successfully!');
    }

    public function addTaskFile(Request $request, Task $task)
    {
        $task->load('project.team.members');
        if ($task->project->is_completed) {
            return $this->sendError('Project already completed!', [], 403);
        }

        $user = auth()->user();

        // Permission check before returning task
        if (
            !$user->hasAnyAppRole(['Admin', 'Super Admin']) &&
            !$task->project->team->members->contains($user->id)
        ) {
            return $this->sendError('Unauthorized action!',[], 403);
        }

        $request->validate([
            'name' => 'required|string|max:50',
            'url' => 'required|url',
            'type' => 'required|string'
        ]);

        $task->attachments()->create([
            'user_id' => $user->id,
            'name' => $request->name,
            'url' => $request->url,
            'type' => $request->type,
        ]);

        return $this->sendResponse([], 'Task attachment added successfully!');
    }

    public function deleteTaskFile(Attachment $attachment)
    {
        $user = auth()->user();

        $attachment->load('task.project.team');
        if ($attachment->task->project->is_completed) {
            return $this->sendError('Project already completed!', [], 403);
        }

        // Permission check before returning task
        if (
            !$user->hasAnyAppRole(['Admin', 'Super Admin']) &&
            !$attachment->user_id == $user->id
            && !$attachment->task->project->team->team_lead_id != $user->id
        ) {
            return $this->sendError('Unauthorized action!',[], 403);
        }

        $attachment->delete();

        return $this->sendResponse(null, 'Task attachment deleted successfully!');
    }

    public function addTaskDiscussion(Request $request, Task $task)
    {
        $task->load('project.team.members');
        if ($task->project->is_completed) {
            return $this->sendError('Project already completed!', [], 403);
        }
        
        $user = auth()->user();

        // Permission check before returning task
        if (
            !$user->hasAnyAppRole(['Admin', 'Super Admin']) &&
            !$task->project->team->members->contains($user->id)
        ) {
            return $this->sendError('Unauthorized action!',[], 403);
        }

        $request->validate([
            'content' => 'required|string|max:1500',
        ]);

        $discussion = $task->discussions()->create([
            'user_id' => $user->id,
            'content' => $request->content,
        ]);

        Notification::send(
            $task->project->team->members->filter(fn ($member) => $member->id !== auth()->id()),
            new AppNotification(
                'New message: ' . substr($discussion->content, 0, 15) . ' ...',
                'discussion',
                $task->id
            )
        );        

        return $this->sendResponse([], 'Task discussion added successfully!');
    }

    public function deleteTaskDiscussion(Discussion $discussion)
    {
        try {
            if ($discussion->created_at->lt(now()->subMinutes(5))) {
                return $this->sendError('Cannot delete discussion after 5 minutes!', [], 403);
            }
            
            $user = auth()->user();

            $discussion->load('task.project.team');
            if ($discussion->task->project->is_completed) {
                return $this->sendError('Project already completed!', [], 403);
            }

            // Permission check before returning task
            if (
                !$user->hasAnyAppRole(['Admin', 'Super Admin']) &&
                $discussion->user_id !== $user->id &&
                $discussion->task?->project?->team?->team_lead_id !== $user->id
            ) {
                return $this->sendError('Unauthorized action!',[], 403);
            }            

            $discussion->delete();

            return $this->sendResponse($discussion, 'Discussion deleted successfully!');
        } catch (\Throwable $th) {
            \Log::info($th->getMessage());
            return $this->sendError('Error deleting discussion!', [], 500);
        }
    }

    public function updateTaskStatus(Task $task, Request $request)
    {
        try {
            $user = auth()->user();
    
            $task->load('project.team');
            if ($task->project->is_completed) {
                return $this->sendError('Project already completed!', [], 403);
            }
    
            if (!$user->hasAnyAppRole(['Admin', 'Super Admin']) && !$task->project->team->team_lead_id != $user->id && $task->assigned_to != $user->id) {
                return $this->sendError('Unauthorized action!',[], 403);
            }

            if ($request->status_id == $task->status_id) {
                return $this->sendResponse(null, 'Task status updated successfully');
            }

            $request->validate([
                'status_id' => 'required|exists:project_task_statuses,id',
            ]);
            $task->update($request->only(['status_id']));
        
            return $this->sendResponse(null, 'Task status updated successfully');
        } catch (\Throwable $th) {
            \Log::info($th->getMessage());
            return $this->sendError('Error updating task status!', [], 500);
        }
    }

}
