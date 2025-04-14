<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\NoteController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\TeamController;
use App\Http\Controllers\DiscussionController;
use App\Http\Controllers\MeetingController;
use App\Http\Controllers\UserInviteController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;

Route::get('/test-endpoint', function (Request $request) {
    return response()->json('Hello, World!');
});

Route::middleware('auth:sanctum')->group(function () {
    Route::middleware(['is_active'])->group(function () {
        // Dashboard
        Route::get('/dashboard', [DashboardController::class, 'dashboard']);
        Route::get('/notifications', [DashboardController::class, 'unreadNotifications']);
        Route::post('/notifications/{notification}/read', [DashboardController::class, 'markNotificationAsRead']);
        Route::post('/notifications/mark-all-as-read', [DashboardController::class, 'markAllNotificationsAsRead']);

        // user related routes
        Route::get('/auth/user', [UserInviteController::class, 'getAuthUser']);
        Route::get('/all-users', [UserInviteController::class, 'getAllUsers']);
        Route::get('/all-unpaginated-users', [UserInviteController::class, 'getAllUnpaginatedUsers']);
        Route::post('/change-user-role/{user}', [UserInviteController::class, 'updateUserAppRole']);
        Route::post('/restrict-user/{user}', [UserInviteController::class, 'restrictUser']);
        Route::post('/activate-user/{user}', [UserInviteController::class, 'activateUser']);
        Route::delete('/delete-user/{user}', [UserInviteController::class, 'deleteUser']);
        Route::put('/update-password', [UserInviteController::class, 'updatePassword']);
        Route::put('/update-user-avatar', [UserInviteController::class, 'updateUserAvatar']);

        // Project Routes
        Route::apiResource('projects', ProjectController::class);
        Route::get('/projects-statuses', [ProjectController::class, 'getProjectStatuses']);
        Route::get('/get-user-projects/{user}', [ProjectController::class, 'getUserProjects']);
        Route::post('projects/{project}/mark-as-completed', [ProjectController::class, 'markProjectComplete']);

        // Task Routes
        Route::apiResource('tasks', TaskController::class);

        // Team Routes
        Route::apiResource('teams', TeamController::class);

        // Add/Remove Team Members
        Route::post('/teams/{team}/add-member', [TeamController::class, 'addMember']);
        Route::post('/teams/{team}/remove-member', [TeamController::class, 'removeMember']);

        // Discussion Routes
        Route::apiResource('discussions', DiscussionController::class);
        
        Route::apiResource('meetings', MeetingController::class);
        Route::get('meetings/project/{project}', [MeetingController::class, 'getMeetingPerProject']);

        Route::get('/all-invites', [UserInviteController::class, 'index']);
        Route::post('/invite', [UserInviteController::class, 'invite']);
        Route::post('/invite/{userInvite}/resend', [UserInviteController::class, 'resend']);
        Route::delete('/invite/{userInvite}', [UserInviteController::class, 'destroy']);

        Route::post('/auth/logout', [AuthenticatedSessionController::class, 'destroy']);

        // notes routes
        Route::get('/get-personal-notes', [NoteController::class, 'getPersonalNote']);
        Route::get('/get-project-notes/{project}', [NoteController::class, 'getProjectNote']);
        Route::post('/notes', [NoteController::class, 'store']);
        Route::put('/notes/{note}', [NoteController::class, 'update']);
        Route::delete('/notes/{note}', [NoteController::class, 'destroy']);
        
    });
});

require __DIR__.'/auth.php';