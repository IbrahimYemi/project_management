<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\TeamController;
use App\Http\Controllers\DiscussionController;
use App\Http\Controllers\MeetingController;

Route::middleware(['auth:sanctum'])->get('/user', function (Request $request) {
    return $request->user();
});

Route::middleware('auth:sanctum')->group(function () {
    // Project Routes
    Route::apiResource('projects', ProjectController::class);

    // Task Routes
    Route::apiResource('projects.tasks', TaskController::class)->shallow();

    // Team Routes
    Route::apiResource('teams', TeamController::class);

    // Add/Remove Team Members
    Route::post('/teams/{team}/add-member', [TeamController::class, 'addMember']);
    Route::post('/teams/{team}/remove-member', [TeamController::class, 'removeMember']);

    // Discussion Routes
    Route::apiResource('discussions', DiscussionController::class);
    
    Route::apiResource('projects.meetings', MeetingController::class)->shallow();
});