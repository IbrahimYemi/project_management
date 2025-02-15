<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\ProjectTaskStatus;
use Illuminate\Http\Request;

class ProjectController extends Controller
{
    // List all projects (Admin and Members can view)
    public function index()
    {
        $user = auth()->user();

        if ($user->hasRole('Admin')) {
            $projects = Project::with(['team', 'status'])->get();
        } else {
            // Team Members - Projects where user is a member
            $projects = Project::whereHas('team.members', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })->with(['team', 'status'])->get();
        }

        return response()->json($projects);
    }

    // Create a new project (Admin only)
    public function store(Request $request)
    {
        $this->authorize('create', auth()->user());

        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'team_id' => 'required|exists:teams,id',
            'status_id' => 'required|exists:project_statuses,id',
            'task_statuses' => 'required|array'
        ]);

        $project = Project::create($request->only(['name', 'description', 'team_id', 'status_id']));

        foreach ($request->only(['task_statuses']) as $value) {
            ProjectTaskStatus::create([
                'project_id' => $project->id,
                'name' => (string) $value
            ]);
        }

        return response()->json($project, 201);
    }

    // Show a specific project (Admin, Team Lead, and Team Members can view)
    public function show(Project $project)
    {
        $this->authorize('view', [auth()->user(), $project]);

        return response()->json($project->load(['team', 'status', 'tasks']));
    }

    // Update a project (Admin only)
    public function update(Request $request, Project $project)
    {
        $this->authorize('update', [auth()->user(), $project]);

        $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'team_id' => 'sometimes|exists:teams,id',
            'status_id' => 'sometimes|exists:project_statuses,id',
        ]);

        $project->update($request->only(['name', 'description', 'team_id', 'status_id']));

        return response()->json($project);
    }

    // Delete a project (Admin only)
    public function destroy(Project $project)
    {
        $this->authorize('create', auth()->user());

        $project->delete();

        return response()->json(['message' => 'Project deleted successfully']);
    }
}
