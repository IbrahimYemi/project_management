<?php

namespace App\Providers;

use App\Models\Discussion;
use App\Models\Project;
use App\Policies\DiscussionPolicy;
use App\Policies\ProjectPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        Discussion::class => DiscussionPolicy::class,
        Project::class => ProjectPolicy::class,
        Task::class => TaskPolicy::class,
        Team::class => TeamPolicy::class,
    ];

    public function boot()
    {
        $this->registerPolicies();
    }
}