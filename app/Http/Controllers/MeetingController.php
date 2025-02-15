<?php

namespace App\Http\Controllers;

use App\Models\Meeting;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class MeetingController extends Controller
{
    // List all meetings for a project (Admin, Team Lead, and Members can view)
    public function index(Project $project)
    {
        $user = auth()->user();

        if (!$project->team->members->contains($user) && $project->team->team_lead_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $meetings = $project->meetings()->with(['team', 'task'])->get();

        return response()->json($meetings);
    }

    // Create a new meeting (Team Lead only)
    public function store(Request $request, Project $project)
    {
        $user = auth()->user();

        if ($project->team->team_lead_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'agenda' => 'required|string|max:255',
            'link' => 'nullable|url',
            'task_id' => 'nullable|exists:tasks,id',
            'date' => 'required|date',
            'time' => 'required|date_format:H:i',
        ]);

        $meeting = $project->meetings()->create([
            'agenda' => $request->agenda,
            'link' => $request->link,
            'team_id' => $project->team_id,
            'task_id' => $request->task_id,
            'date' => $request->date,
            'time' => $request->time,
        ]);

        return response()->json($meeting, 201);
    }

    // Update a meeting (Team Lead only)
    public function update(Request $request, Meeting $meeting)
    {
        $user = auth()->user();

        if ($meeting->project->team->team_lead_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'agenda' => 'sometimes|string|max:255',
            'link' => 'nullable|url',
            'task_id' => 'nullable|exists:tasks,id',
            'date' => 'sometimes|date',
            'time' => 'sometimes|date_format:H:i',
        ]);

        $meeting->update($request->only(['agenda', 'link', 'task_id', 'date', 'time']));

        return response()->json($meeting);
    }

    // Delete a meeting (Team Lead only)
    public function destroy(Meeting $meeting)
    {
        $user = auth()->user();

        if ($meeting->project->team->team_lead_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $meeting->delete();

        return response()->json(['message' => 'Meeting deleted successfully']);
    }
}
