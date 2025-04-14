<?php

namespace App\Providers;

use App\Models\Discussion;
use App\Policies\DiscussionPolicy;
use App\Models\Project;
use App\Policies\ProjectPolicy;
use App\Models\UserInvite;
use App\Policies\UserInvitePolicy;
use App\Models\Task;
use App\Policies\TaskPolicy;
use App\Models\Team;
use App\Policies\TeamPolicy;
use App\Models\Note;
use App\Policies\NotePolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        Discussion::class => DiscussionPolicy::class,
        Project::class => ProjectPolicy::class,
        Task::class => TaskPolicy::class,
        Team::class => TeamPolicy::class,
        UserInvite::class => UserInvitePolicy::class,
        User::class => UserInvitePolicy::class,
        Note::class => NotePolicy::class,
    ];

    public function boot()
    {
        $this->registerPolicies();
    }
}