<?php

namespace App\Http\Controllers;

use App\Http\Resources\MeetingCollectionResource;
use App\Models\Meeting;
use App\Models\Project;
use App\Models\Task;
use App\Notifications\AppNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Notification;

class MeetingController extends Controller
{
    public function index(Request $request) 
    {
        $user = auth()->user();
            
        $query = $request->query('query');
        $startDate = $request->query('start');
        $endDate = $request->query('end');
        $perPage = 10;
        $page = request('page', 1);

        // Get user's teams
        $teams = $user->teams()->pluck('teams.id');

        // Get projects related to those teams
        $projects = Project::whereIn('team_id', $teams)->pluck('projects.id')->toArray();

        // Get meetings linked to the user's teams, projects, or tasks
        $meetings = Meeting::with(['team', 'project'])->where(function ($q) use ($projects) {
                $q->whereIn('project_id', $projects);
            })
            ->orderBy("created_at", "desc");

        // Apply search filter (agenda)
        if (!empty($query)) {
            $meetings->where('agenda', 'like', '%' . $query . '%');
        }

        // Apply date range filter
        if (!empty($startDate) && !empty($endDate)) {
            $startDate = Carbon::parse($startDate)->startOfDay();
            $endDate = Carbon::parse($endDate)->endOfDay();

            $meetings->whereBetween('date', [$startDate, $endDate]);
        }

        // Paginate results
        $meetings = $meetings->paginate(20);
        return $this->sendPaginatedResponse(MeetingCollectionResource::collection($meetings), $page, $perPage);
    }

    public function store(Request $request)
    {
        $user = auth()->user();

        if (!$user->hasAnyAppRole(['Admin', 'Team Lead', 'Super Admin'])) {
            return $this->sendError('Unauthorized, you can not create a meeting schedule!', 403);
        }

        $request->validate([
            'agenda' => 'required|string|max:255',
            'link' => 'nullable|url',
            'task_id' => 'nullable|exists:tasks,id',
            'project_id' => 'required|exists:projects,id',
            'team_id' => 'nullable|exists:teams,id',
            'date' => 'required|date|after_or_equal:today',
            'time' => 'required|date_format:H:i',
        ]);

        $project = Project::with('team.members')->find($request->project_id);
        if (!$project) {
            return $this->sendError('Project not found', 404);
        }

        if ($project->is_completed) {
            return $this->sendError('Project already completed!', [], 403);
        }

        $meeting = $project->meetings()->create([
            'agenda' => $request->agenda,
            'link' => $request->link,
            'team_id' => $project->team_id,
            'task_id' => $request->task_id,
            'date' => $request->date,
            'time' => $request->time,
        ]);

        $teamMembers = $project->team->members;
        Notification::send($teamMembers, new AppNotification(
            'A new meeting as just been scheduled on ' . $project->name .' project. Time: ' . $meeting->time . ', date: ' . $meeting->date,
            'meeting',
            $meeting->link
        ));

        return $this->sendResponse(null, 'Meeting created successfully');
    }

    // Update a meeting (Team Lead only)
    public function update(Request $request, Meeting $meeting)
    {
        $user = auth()->user();

        if (!$user->hasAnyAppRole(['Admin', 'Team Lead', 'Super Admin'])) {
            return $this->sendError('Unauthorized, you can not edit a meeting schedule!', 403);
        }

        $meeting->load('project');
        if ($meeting->project->is_completed) {
            return $this->sendError('Project already completed!', [], 403);
        }

        $request->validate([
            'agenda' => 'sometimes|string|max:255',
            'link' => 'nullable|url',
            'task_id' => 'nullable|exists:tasks,id',
            'date' => 'sometimes|date|after_or_equal:today',
            'time' => 'sometimes|date_format:H:i:s',
        ]);

        $meeting->update($request->only(['agenda', 'link', 'task_id', 'date', 'time']));

        $teamMembers = $meeting->project->team->members;
        Notification::send($teamMembers, new AppNotification(
            'A meeting schedule has been updated on ' . $meeting->project->name . 'project. Time: ' . $meeting->time . ', date: ' . $meeting->date,
            'meeting',
            $meeting->link
        ));

        return $this->sendResponse(null, 'Meeting edited successfully');
    }

    // Delete a meeting (Team Lead only)
    public function destroy(Meeting $meeting)
    {
        $user = auth()->user();

        if (!$user->hasAnyAppRole(['Admin', 'Team Lead', 'Super Admin'])) {
            return $this->sendError('Unauthorized, you can not edit a meeting schedule!', 403);
        }

        $meeting->load('project');
        if ($meeting->project->is_completed) {
            return $this->sendError('Project already completed!', [], 403);
        }

        $meeting->delete();

        // send cancelation notification to team members if not yet passed on date and time
        if ($meeting->date < now()) {
            $teamMembers = $meeting->project->team->members;
            Notification::send($teamMembers, new AppNotification(
                'A meeting schedule has been canceled for ' . $meeting->project->name,
                'meeting',
                $meeting->link
            ));
        }

        return $this->sendResponse(null, 'Meeting deleted successfully');
    }

    public function getMeetingPerProject(Project $project)
    {
        $perPage = 10;
        $page = request('page', 1);

        $user = auth()->user();

        if (!$project->team->members->contains($user)) {
            return $this->sendResponse(MeetingCollectionResource::collection([]));
        }

        $meetings = Meeting::where('project_id', $project->id)->with(['team', 'project'])->paginate(10);
        
        return $this->sendPaginatedResponse(MeetingCollectionResource::collection($meetings), $page, $perPage);
    }
}
