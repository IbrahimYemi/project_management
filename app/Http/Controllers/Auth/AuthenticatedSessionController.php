<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Hash;

class AuthenticatedSessionController extends Controller
{
    /**
     * Handle an incoming authentication request.
     */
    public function store(Request $request)
    {
    
        $user = User::where('email', $request->email)->first();
        
        if (!$user || !Hash::check($request->password, $user->password)) {
            return $this->sendError('Invalid credentials');
        }

        if (!$user->is_active) {
            return $this->sendError('Your account is inactive. Please contact admin.', [], 403);
        }
        return $this->sendAuthResponse($user, 'Login successful');
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): Response
    {
        // Revoke the current access token
        $request->user()->currentAccessToken()->delete();

        return response()->noContent();
    }

    /**
     * Send login token to user's email.
     */
    public function sendLoginToken(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return $this->sendError('Invalid credentials', [], 400);
        }
        
        if ($user->login_token_requested_at) {
            $minutesSinceLastRequest = $user->login_token_requested_at->diffInMinutes(now());
        
            if ($minutesSinceLastRequest < 5) {
                return $this->sendError('A login token was recently sent. Please wait before requesting a new one.', [], 429);
            }
        }

        // Generate token
        $token = $user->generateLoginToken();

        // Send token via email
        Mail::raw("Your login token is: $token", function ($message) use ($user) {
            $message->to($user->email)
                ->subject('Your Login Token');
        });

        return $this->sendResponse(null, 'Login token sent successfully.');
    }

    /**
     * Login user using login token.
     */
    public function loginWithToken(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'login_token' => 'required|string',
        ]);

        $user = User::where('email', $request->email)
            ->where('login_token', $request->login_token)
            ->first();

        if (!$user) {
            return $this->sendError('Invalid credentials', [], 400);
        }
        
        if (!$user->is_active) {
            return $this->sendError('Your account is inactive. Please contact admin.', [], 403);
        }

        return $this->sendAuthResponse($user, 'Login successful');
    }
}
