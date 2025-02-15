<?php

namespace App\Http\Controllers;

use App\Models\Team;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Spatie\Permission\Models\Role;

class TeamController extends Controller
{
    // List all teams (Admin only)
    public function index()
    {
        $user = auth()->user();

        if ($user->hasRole('Admin')) {
            // Admin can see all teams
            $teams = Team::with(['teamLead', 'members'])->get();
        } else {
            // members see the teams they belong to
            $teams = Team::with(['teamLead', 'members'])
                ->whereHas('members', function ($query) use ($user) {
                    $query->where('user_id', $user->id);
                })
                ->get();
        }

        return response()->json($teams);
    }

    // Show a specific team (Admin and Team Lead can view)
    public function show(Team $team)
    {
        $this->authorize('member', auth()->user());

        return response()->json($team->load(['teamLead', 'members']));
    }

    public function store(Request $request)
    {
        $this->authorize('create', auth()->user());

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
                $team->members()->attach($request->members);
            }

            DB::commit();

            return response()->json($team->load('members'), 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Failed to create team', 'message' => $e->getMessage()], 500);
        }
    }

    public function update(Request $request, Team $team)
    {
        $this->authorize('create', auth()->user());

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
                $team->members()->sync($request->members);
            }

            DB::commit();

            return response()->json($team->load('members'));
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Failed to update team', 'message' => $e->getMessage()], 500);
        }
    }

    // Delete a team (Admin only)
    public function destroy(Team $team)
    {
        $this->authorize('create', auth()->user());

        $team->delete();

        return response()->json(['message' => 'Team deleted successfully']);
    }

    // Add a member to a team (Admin only)
    public function addMember(Request $request, Team $team)
    {
        $this->authorize('add', auth()->user());

        $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        $user = User::findOrFail($request->user_id);

        // Ensure the user is not already a member of the team
        if ($team->members->contains($user)) {
            return response()->json(['message' => 'User is already a member of this team'], 400);
        }

        $team->members()->attach($user);

        return response()->json(['message' => 'Member added to team successfully']);
    }

    // Remove a member from a team (Admin only)
    public function removeMember(Request $request, Team $team)
    {
        $this->authorize('remove', auth()->user());

        $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        $user = User::findOrFail($request->user_id);

        // Ensure the user is a member of the team
        if (!$team->members->contains($user)) {
            return response()->json(['message' => 'User is not a member of this team'], 400);
        }

        $team->members()->detach($user);

        return response()->json(['message' => 'Member removed from team successfully']);
    }
}