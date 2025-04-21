<?php

namespace App\Http\Controllers;

use App\Http\Resources\NotificationResource;
use Illuminate\Http\Request;
use App\Models\Project;
use App\Models\Task;
use App\Models\Meeting;
use App\Models\Team;
use App\Models\ProjectStatus;
use Illuminate\Support\Facades\Cache;

class DashboardController extends Controller
{

    public function dashboard()
    {
        $cacheKey = 'dashboard_data_' . auth()->id();
        $cacheDuration = now()->addMinutes(3);

        $data = Cache::remember($cacheKey, $cacheDuration, function () {
            $totalProjects = Project::Accessible()->count();

            $projectStatuses = ProjectStatus::withCount(['projects' => function ($query) {
                $query->Accessible();
            }])->get()->map(function ($status) use ($totalProjects) {
                $percentage = $totalProjects > 0
                    ? round(($status->projects_count / $totalProjects) * 100, 2)
                    : 0;
                return [
                    'name' => $status->name,
                    'value' => $percentage,
                ];
            });

            $allProjectsData = Project::Accessible()
                ->with(['status:id,name', 'tasks.status:id,percentage'])
                ->get()
                ->map(function ($project) {
                    return [
                        'name' => $project->name,
                        'statusName' => $project->status->name ?? 'Unknown',
                        'value' => $project->getProgressPercentage() ?? 0,
                    ];
                });

            $tasks = Task::with(['owner:id,name', 'status:id,percentage'])
                ->get()
                ->map(function ($task) {
                    return [
                        'name' => $task->name,
                        'value' => $task->status->percentage ?? 0,
                        'owner' => $task->owner->name ?? 'Unknown',
                    ];
                });

            $teams = Team::withCount(['projects', 'members'])
                ->with('teamLead:id,name')
                ->get()
                ->map(function ($team) {
                    return [
                        'id' => $team->id,
                        'name' => $team->name,
                        'teamLead' => $team->teamLead->name ?? 'N/A',
                        'members' => $team->members_count,
                        'projectCount' => $team->projects_count,
                    ];
                });

            return [
                'projects' => $projectStatuses,
                'allProjectsData' => $allProjectsData,
                'tasks' => $tasks,
                'teams' => $teams,
            ];
        });

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
