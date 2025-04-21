<?php

namespace App\Http\Controllers;

use App\Http\Resources\AllProjectResource;
use App\Http\Resources\ProjectCollectionResource;
use App\Models\User;
use App\Models\Project;
use App\Models\ProjectTaskStatus;
use App\Models\ProjectStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Pagination\LengthAwarePaginator;

class ProjectController extends Controller
{
    // List all projects (Admin and Members can view)
    public function index()
    {
        $perPage = 10;
        $page = request('page', 1);

        $projects = Project::Accessible()->with(['taskStatuses', 'status', 'team.members', 'tasks.status', ]);

        $projectsCollection = AllProjectResource::collection($projects->paginate(10));
        
        return $this->sendPaginatedResponse($projectsCollection, $page, $perPage);
    }

    public function getUserProjects(User $user)
    {
        $projects = Project::Accessible($user);
        
        return $this->sendResponse($projects->get()->map(function($pr) {
            return [
                'id' => $pr->id,
                'name' => $pr->name,
            ];
        }));
    }

    // Create a new project (Admin only)
    public function store(Request $request)
    {
        $this->authorize('create', Project::class);

        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'team_id' => 'required|exists:teams,id',
            'status_id' => 'required|exists:project_statuses,id',
            'task_statuses' => 'required|array',
            'task_statuses.*.name' => 'required|string',
            'task_statuses.*.percentage' => 'required|numeric|min:0|max:100',
        ], [
            'name.required' => 'The project name is required.',
            'name.string' => 'The project name must be a string.',
            'name.max' => 'The project name must not exceed 255 characters.',

            'description.string' => 'The description must be a valid string.',

            'team_id.required' => 'Please select a team for the project.',
            'team_id.exists' => 'The selected team is invalid.',

            'status_id.required' => 'Please choose a project status.',
            'status_id.exists' => 'The selected project status is invalid.',

            'task_statuses.required' => 'Please provide at least one task status.',
            'task_statuses.array' => 'Task statuses must be an array.',

            'task_statuses.*.name.required' => 'Each task status must have a name.',
            'task_statuses.*.name.string' => 'Task status names must be strings.',

            'task_statuses.*.percentage.required' => 'Each task status must have a percentage.',
            'task_statuses.*.percentage.numeric' => 'The percentage must be a number.',
            'task_statuses.*.percentage.min' => 'The percentage must be at least 0.',
            'task_statuses.*.percentage.max' => 'The percentage must not exceed 100.',
        ]);

        DB::beginTransaction();

        try {
            $project = Project::create($request->only(['name', 'description', 'team_id', 'status_id']));

            foreach ($request->task_statuses as $taskStatus) {
                ProjectTaskStatus::create([
                    'project_id' => $project->id,
                    'name' => (string) $taskStatus['name'],
                    'percentage' => (string) $taskStatus['percentage'],
                ]);
            }

            DB::commit();

            return $this->sendResponse([]);
        } catch (\Throwable $e) {
            DB::rollBack();
            \Log::error('Project creation failed', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Something went wrong while creating the project.'], 500);
        }
    }

    // Show a specific project (Admin, Team Lead, and Team Members can view)
    public function show(Project $project)
    {
        $this->authorize('view', $project);

        // Eager load everything needed
        $project->load([
            'team.members',
            'team.teamLead',
            'status',
            'tasks',
            'meetings',
            'taskStatuses',
        ])->loadCount('tasks');

        // Eager‑load counts on the team
        if ($project->relationLoaded('team')) {
            $project->team->loadCount(['projects', 'members']);
        }
        if ($project->relationLoaded('tasks')) {
            $project->tasks->load(['assignedUser', 'status', 'priority']);
        }

        return $this->sendResponse(ProjectCollectionResource::make($project));
    }

    // Update a project (Admins only)
    public function update(Request $request, Project $project)
    {
        if ($project->is_completed) {
            return $this->sendError('Project already completed!', [], 403);
        }

        $this->authorize('update', $project);

        $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'team_id' => 'sometimes|exists:teams,id',
            'status_id' => 'sometimes|exists:project_statuses,id',
        ]);


        DB::beginTransaction();

        try {
            if ($request->input('status_id') && $status = ProjectStatus::find($request->input('status_id'))) {
                if ($status->name === 'Completed') {
                    $project->markAsCompleted();
                }
            }
            $project->update($request->only(['name', 'description', 'team_id', 'status_id']));

            DB::commit();

            return $this->sendResponse([], 'Project updated successfully!');
        } catch (\Throwable $e) {
            DB::rollBack();
            \Log::error('Project edit failed', ['error' => $e->getMessage()]);
            return $this->sendError('Something went wrong while editing the project.', [], 500);
        }
    }

    // Delete a project (Admin only)
    public function destroy(Project $project)
    {
        $this->authorize('create', Project::class);

        $project->delete();

        return response()->json(['message' => 'Project deleted successfully']);
    }

    public function getProjectStatuses()
    {
        return $this->sendResponse(ProjectStatus::query()->orderBy('name')->get());
    }

    public function markProjectComplete(Project $project)
    {
        $this->authorize('create', Project::class);

        $project->markAsCompleted();

        return $this->sendResponse([], 'Project marked completed successfully!');
    }
}
