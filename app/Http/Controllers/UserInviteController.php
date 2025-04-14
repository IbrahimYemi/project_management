<?php

namespace App\Http\Controllers;

use App\Mail\UserInviteMail;
use App\Models\User;
use App\Models\UserInvite;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\{Auth, Hash, Mail};

class UserInviteController extends Controller
{
    public function getAllUsers(Request $request)
    {
        $perPage = $request->query('per_page', 20);
        $query = $request->query('query');

        $users = User::query()
            ->orderByRaw("FIELD(app_role, 'Super Admin', 'Admin', 'Teamlead', 'Member')")
            ->latest();

        if ($query) {
            $users->where(function ($q) use ($query) {
                $q->where('name', 'like', '%' . $query . '%')
                ->orWhere('email', 'like', '%' . $query . '%');
            });
        }

        return $this->sendResponse($users->paginate($perPage));
    }

    public function getAllUnpaginatedUsers(Request $request)
    {
        $query = $request->query('query');

        $users = User::query()->orderBy('name');

        if ($query) {
            $users->where(function ($q) use ($query) {
                $q->where('name', 'like', '%' . $query . '%')
                ->orWhere('email', 'like', '%' . $query . '%');
            });
        }

        return $this->sendResponse($users->get());
    }
    public function restrictUser(User $user)
    {
        if ($this->cantDeleteAdmin($user) === true) {
            return $this->sendError('Action unauthorized');
        }
        
        if ($user->is_active == false) {
            return $this->sendError('User is already restricted.');
        }

        $user->update(['is_active' => false]);

        return $this->sendResponse([], 'User has been restricted successfully.');
    }

    public function activateUser(User $user)
    {
        if ($this->cantDeleteAdmin($user) === true) {
            return $this->sendError('Action unauthorized');
        }
        
        if ($user->is_active == true) {
            return $this->sendError('User is already active.');
        }

        $user->update(['is_active' => true]);

        return $this->sendResponse([], 'User has been activated successfully.');
    }

    public function deleteUser(User $user)
    {
        if ($this->cantDeleteAdmin($user) === true) {
            return $this->sendError('Action unauthorized');
        }
        
        $user->delete();

        return $this->sendResponse([], 'User has been deleted successfully.');
    }

    public function updateUserAppRole(Request $request, User $user)
    {
        $this->authorize('update', UserInvite::class);

        $request->validate([
            'app_role' => 'required|string|in:Admin,Teamlead,Member',
        ]);
        
        // Check if the user is a Super Admin
        if ($user->app_role === 'Super Admin') {
            return $this->sendError('You cannot update the role of a Super Admin.');
        }
        // Check if the new role is Super Admin
        if ($request->input('app_role') === 'Super Admin') {
            return $this->sendError('You cannot assign the Super Admin role.');
        }
        // Check if the user is trying to assign the same role
        if ($user->app_role === $request->input('app_role')) {
            return $this->sendError('User already has this role.');
        }

        // Update the user's role
        $user->app_role = $request->input('app_role');
        $user->save();

        return $this->sendResponse($user, 'User role updated successfully.');
    }

    public function updatePassword(Request $request)
    {
        $request->validate([
            'old_password' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = Auth::user();

        // Check if old password matches
        if (!Hash::check($request->old_password, $user->password)) {
            return $this->sendError('Old password is incorrect', [], 422);
        }

        // Update password
        $user->update([
            'password' => Hash::make($request->password),
        ]);

        return $this->sendResponse(null, 'Password updated successfully');
    }

    public function updateUserAvatar(Request $request)
    {
        $request->validate([
            'avatar' => 'nullable|string|url',
            'name' => 'required|string|max:150'
        ]);

        $user = Auth::user();

        // Update avatar
        $user->update([
            'name' => $request->name,
            'avatar' => $request->avatar,
        ]);

        return $this->sendResponse($user, 'avatar updated successfully');
    }
    
    public function getAuthUser()
    {
        return $this->sendResponse(auth()->user());
    }

    public function cantDeleteAdmin(User $user)
    {
        if ($user->app_role === 'Super Admin') {
            return true;
        }
        return false;
    }
    
    /**
     * Summary of index
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $invites = UserInvite::query();
        $query = $request->query('query');

        if ($request->has('query')) {
            $invites = $invites->where('name', 'like', '%' . $query . '%')
            ->orWhere('email', 'like', '%' . $query . '%');
        }

        return $this->sendResponse($invites->orderBy("created_at","desc")->paginate(20));
    }

    /**
     * Invite a user (store).
     */
    public function invite(Request $request)
    {
        $this->authorize('create', UserInvite::class);

        $validated = $request->validate([
            'email' => 'required|email|unique:user_invites,email',
            'name' => 'required|string',
        ]);

        if( User::where('email', $validated['email'])->exists() ) {
            return $this->sendError('User already exists', [], 400);
        }

        $token = strtoupper(Str::random(8));

        $invite = UserInvite::create([
            'email' => $validated['email'],
            'token' => $token,
            'name' => $validated['name'],
        ]);
        
        Mail::to($invite->email)->send(new UserInviteMail($invite));

        return $this->sendResponse(null, 'Invitation sent successfully');
    }

    /**
     * Resend Invite (Optional)
     */
    public function resend(UserInvite $userInvite)
    {
        $this->authorize('resend', UserInvite::class);

        if ($userInvite->is_accepted) {
            return $this->sendError('Invite already accepted');
        }

        $userInvite->update(['token' => strtoupper(Str::random(8))]);

        // Send email again
        Mail::to($userInvite->email)->send(new UserInviteMail($userInvite, 'Resending invite!'));

        return $this->sendResponse(null, 'Invitation resent successfully');
    }

    /**
     * Delete an invite.
     */
    public function destroy(UserInvite $userInvite)
    {
        $this->authorize('delete', UserInvite::class);

        $userInvite->delete();

        return $this->sendResponse(null, 'Invite deleted successfully');
    }
}
