<?php

use App\Models\User;
use App\Models\Discussion;
use Spatie\Permission\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Set up roles for Spatie Permissions
    Role::create(['name' => 'Admin']);
    Role::create(['name' => 'User']);
});

it('creates a discussion', function () {
    $discussion = Discussion::factory()->create();

    expect($discussion)->not->toBeNull();
});

it('allows creator to update discussion', function () {
    $user = User::factory()->create();
    $discussion = Discussion::factory()->create(['user_id' => $user->id]);

    $response = actingAs($user)->putJson("/api/discussions/{$discussion->id}", [
        'content' => 'Updated content',
    ]);

    $response->assertStatus(200);
    expect($discussion->fresh()->content)->toBe('Updated content');
});

it('allows admin to update any discussion', function () {
    $admin = User::factory()->create();
    $admin->assignRole('Admin');

    Discussion::factory()->create();
    $discussion = Discussion::first();

    $response = actingAs($admin)->putJson("/api/discussions/{$discussion->id}", [
        'content' => 'Updated by Admin',
    ]);

    $response->assertStatus(200);
    expect($discussion->fresh()->content)->toBe('Updated by Admin');
});

it('prevents non-creator and non-admin from updating discussion', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $discussion = Discussion::factory()->create(['user_id' => $otherUser->id]);

    $response = actingAs($user)->putJson("/api/discussions/{$discussion->id}", [
        'content' => 'Should fail',
    ]);

    $response->assertStatus(403);
});

it('allows creator to delete their discussion', function () {
    $user = User::factory()->create();
    $discussion = Discussion::factory()->create(['user_id' => $user->id]);

    $response = actingAs($user)->deleteJson("/api/discussions/{$discussion->id}");

    $response->assertStatus(200);
    expect(Discussion::find($discussion->id))->toBeNull();
});

it('allows admin to delete any discussion', function () {
    $admin = User::factory()->create();
    $admin->assignRole('Admin');

    $discussion = Discussion::factory()->create();

    $response = actingAs($admin)->deleteJson("/api/discussions/{$discussion->id}");

    $response->assertStatus(200);
    expect(Discussion::find($discussion->id))->toBeNull();
});

it('prevents non-creator and non-admin from deleting discussion', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $discussion = Discussion::factory()->create(['user_id' => $otherUser->id]);

    $response = actingAs($user)->deleteJson("/api/discussions/{$discussion->id}");

    $response->assertStatus(403);
    expect(Discussion::find($discussion->id))->not->toBeNull();
});
