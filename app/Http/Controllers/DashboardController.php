<?php

namespace App\Http\Controllers;

use App\Http\Resources\NotificationResource;
use Illuminate\Http\Request;
use App\Models\Project;
use App\Models\Task;
use App\Models\Meeting;
use App\Models\Team;
use App\Models\ProjectStatus;

class DashboardController extends Controller
{
    
    public function dashboard()
    {
        // Get total number of projects
        $totalProjects = Project::count();

        // Avoid division by zero
        if ($totalProjects === 0) {
            $projectStatuses = collect([]);
        } else {
            // Group projects by status and return percentage
            $projectStatuses = ProjectStatus::withCount('projects')->get()->map(function ($status) use ($totalProjects) {
                $percentage = round(($status->projects_count / $totalProjects) * 100, 2); // You can round to whole numbers if you prefer
                return [
                    'name' => $status->name,
                    'value' => $percentage,
                ];
            });
        }
    
        // Get all projects with their status and value/progress (assume value = progress %)
        $allProjectsData = Project::with('status')
            ->get()
            ->map(function ($project) {
                return [
                    'name' => $project->name,
                    'statusName' => $project->status?->name ?? 'Unknown',
                    'value' => $project->getProgressPercentage() ?? 0,
                ];
            });
    
        // Get tasks with owner name and progress (value)
        $tasks = Task::with('owner', 'status')
            ->get()
            ->map(function ($task) {
                return [
                    'name' => $task->name,
                    'value' => $task->status?->percentage ?? 0,
                    'owner' => $task->owner?->name ?? 'Unknown',
                ];
            });
    
        // Teams with their lead and stats
        $teams = Team::withCount('projects', 'members')
            ->with('teamLead')
            ->get()
            ->map(function ($team) {
                return [
                    'id' => $team->id,
                    'name' => $team->name,
                    'teamLead' => $team->teamLead?->name ?? 'N/A',
                    'members' => $team->members_count,
                    'projectCount' => $team->projects_count,
                ];
            });
    
        // Final data
        $data = [
            'projects' => $projectStatuses,
            'allProjectsData' => $allProjectsData,
            'tasks' => $tasks,
            'teams' => $teams,
        ];
    
        return $this->sendResponse($data);
    }    

    public function getNotifications()
    {
        return $this->sendResponse(NotificationResource::collection(auth()->user()->notifications));
    }

    public function unreadNotifications()
    {
        return $this->sendResponse(NotificationResource::collection(auth()->user()->unreadNotifications));
    }

    public function markNotificationAsRead($id)
    {
        $notification = auth()->user()->notifications()->findOrFail($id);
        $notification->markAsRead();

        return $this->sendResponse('Notification marked as read');
    }

    public function markAllNotificationsAsRead()
    {
        auth()->user()->unreadNotifications->markAsRead();

        return $this->sendResponse('All notifications marked as read');
    }

}
