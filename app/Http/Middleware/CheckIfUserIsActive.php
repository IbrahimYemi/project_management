<?php

namespace App\Http\Middleware;

use App\Traits\ApiResponseTrait;
use Closure;
use Illuminate\Http\Request;

class CheckIfUserIsActive
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if ($user && !$user->is_active) {
            $user->currentAccessToken()->delete();;

            if ($request->expectsJson()) {
                return ApiResponseTrait::sendError('Your account is inactive. Please contact admin.', [], 401);
            }
        }

        return $next($request);
    }
}
