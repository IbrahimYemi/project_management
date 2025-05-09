<?php

namespace App\Http\Controllers;

use App\Http\Resources\TeamCollectionResource;
use App\Models\Team;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TeamController extends Controller
{
    // List all teams (Admin only)
    public function index()
    {
        $user = auth()->user();

        if ($user->hasAnyAppRole(['Super Admin', 'Admin'])) {
            // Admin can see all teams
            $teams = Team::with(['teamLead', 'members', 'projects.taskStatuses'])->get();
        } else {
            // members see the teams they belong to
            $teams = Team::with(['teamLead', 'members', 'projects.taskStatuses'])
                ->whereHas('members', function ($query) use ($user) {
                    $query->where('user_id', $user->id);
                })
                ->get();
        }

        return $this->sendResponse(TeamCollectionResource::collection($teams));
    }

    // Show a specific team (Admin and Team Lead can view)
    public function show(Team $team)
    {
        $this->authorize('member', $team);

        return $this->sendResponse(TeamCollectionResource::make($team->load(['teamLead', 'members', 'projects.taskStatuses'])));
    }

    public function store(Request $request)
    {
        $this->authorize('create', Team::class);

        $request->validate([
            'name' => 'required|string|max:255',
            'team_lead_id' => 'required|exists:users,id',
            'members' => 'nullable|array',
            'members.*' => 'exists:users,id',
        ]);

        DB::beginTransaction();

        try {
            $team = Team::create([
                'name' => $request->name,
                'team_lead_id' => $request->team_lead_id,
            ]);

            if ($request->filled('members')) {
                $membersWithUUIDs = collect($request->members)->mapWithKeys(function ($memberId) {
                    return [
                        $memberId => ['id' => (string) Str::orderedUuid()]
                    ];
                });
            
                $team->members()->attach($membersWithUUIDs);
            }

            DB::commit();

            return $this->sendResponse($team->load('members'), 'Team created successfully', 201);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Failed to create team', [
                'error' => $e->getMessage(),
                'request' => $request->all(),
            ]);
            return $this->sendError('Failed to create team', [], 500);
        }
    }

    public function update(Request $request, Team $team)
    {
        $this->authorize('create', Team::class);

        $request->validate([
            'name' => 'sometimes|string|max:255',
            'team_lead_id' => 'sometimes|exists:users,id',
            'members' => 'nullable|array',
            'members.*' => 'exists:users,id',
        ]);

        DB::beginTransaction();

        try {
            $team->update($request->only(['name', 'team_lead_id']));

            if ($request->filled('members')) {
                $membersWithUUIDs = collect($request->members)->mapWithKeys(function ($memberId) {
                    return [
                        $memberId => ['id' => (string) Str::orderedUuid()]
                    ];
                });
                $team->members()->sync($membersWithUUIDs);
            }

            DB::commit();

            return $this->sendResponse($team->load('members'));
        } catch (\Exception $e) {
            \Log::info($e->getMessage());
            DB::rollBack();
            return $this->sendError('Failed to update team', [], 500);
        }
    }

    // Delete a team (Admin only)
    public function destroy(Team $team)
    {
        $this->authorize('create', Team::class);

        $team->delete();

        return $this->sendResponse('Team deleted successfully');
    }

    // Add a member to a team (Admin only)
    public function addMember(Request $request, Team $team)
    {
        $this->authorize('add', Team::class);

        $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        $user = User::findOrFail($request->user_id);

        // Ensure the user is not already a member of the team
        if ($team->members->contains($user)) {
            return $this->sendError('User is already a member of this team', [], 400);
        }

        $team->members()->attach($user);

        return $this->sendResponse('Member added to team successfully');
    }

    // Remove a member from a team (Admin only)
    public function removeMember(Request $request, Team $team)
    {
        $this->authorize('remove', Team::class);

        $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        $user = User::findOrFail($request->user_id);

        // Ensure the user is a member of the team
        if (!$team->members->contains($user)) {
            return $this->sendError('User is not a member of this team', [], 400);
        }

        $team->members()->detach($user);

        return $this->sendResponse('Member removed from team successfully');
    }
}