<?php

namespace App\Http\Controllers;

use App\Models\Discussion;
use App\Models\Project;
use App\Models\Task;
use Illuminate\Http\Request;

class DiscussionController extends Controller
{
    // List all discussions for a project or task
    public function index(Request $request)
    {
        $request->validate([
            'discussable_type' => 'required|in:project,task',
            'discussable_id' => 'required|integer',
        ]);

        $discussable = $this->getDiscussable($request->discussable_type, $request->discussable_id);

        if (!$this->authorizeAccess($discussable)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $discussions = $discussable->discussions()->with(['user'])->get();

        return response()->json($discussions);
    }

    // Create a new discussion for a project or task
    public function store(Request $request)
    {
        $request->validate([
            'discussable_type' => 'required|in:project,task',
            'discussable_id' => 'required|integer',
            'content' => 'required|string',
            'tagged_users' => 'nullable|array',
            'tagged_users.*' => 'exists:users,id',
        ]);

        $discussable = $this->getDiscussable($request->discussable_type, $request->discussable_id);

        if (!$this->authorizeAccess($discussable)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $discussion = $discussable->discussions()->create([
            'content' => $request->content,
            'user_id' => auth()->id(),
            'tagged_users' => $request->tagged_users,
        ]);

        return response()->json($discussion, 201);
    }

    // Show a specific discussion
    public function show(Discussion $discussion)
    {
        $discussable = $discussion->discussable;

        if (!$this->authorizeAccess($discussable)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return response()->json($discussion->load(['user']));
    }

    // Update a discussion
    public function update(Request $request, Discussion $discussion)
    {
        $this->authorize('update', $discussion);

        $request->validate([
            'content' => 'sometimes|string',
            'tagged_users' => 'nullable|array',
            'tagged_users.*' => 'exists:users,id',
        ]);

        $discussion->update([
            'content' => $request->input('content', $discussion->content),
            'tagged_users' => $request->input('tagged_users', $discussion->tagged_users),
        ]);

        return response()->json($discussion);
    }

    // Delete a discussion
    public function destroy(Discussion $discussion)
    {
        $this->authorize('delete', $discussion);

        $discussion->delete();

        return response()->json(['message' => 'Discussion deleted successfully']);
    }

    private function getDiscussable($type, $id)
    {
        return $type === 'project'
            ? Project::findOrFail($id)
            : Task::findOrFail($id);
    }

    private function authorizeAccess($discussable)
    {
        $user = auth()->user();

        if ($discussable instanceof Project) {
            return $user->hasRole('Admin') ||
                   $discussable->team->team_lead_id === $user->id ||
                   $discussable->team->members->contains($user);
        }

        if ($discussable instanceof Task) {
            return $user->hasRole('Admin') ||
                   $discussable->project->team->team_lead_id === $user->id ||
                   $discussable->assigned_to === $user->id;
        }

        return false;
    }
}

