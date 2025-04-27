<?php

namespace App\Http\Controllers;

use Illuminate\Support\Str;
use App\Traits\ApiResponseTrait;
use Illuminate\Support\Facades\Auth;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

abstract class Controller
{

    use ApiResponseTrait, AuthorizesRequests;

    public function uploadFile($file, $role)
    {
        if ($file->isValid()) {
            $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();
            $path = public_path("uploads/role-base/{$role}");

            // Ensure the directory exists
            if (!file_exists($path)) {
                mkdir($path, 0777, true);
            }

            $file->move($path, $filename);
            return "uploads/role-base/{$role}/" . $filename;
        }

        return null;
    }

    public function deleteFile($file)
    {
        // check if a web url then parse and unlink. else unlink and return boolean
        if (strpos($file, 'http') === 0) {
            $parsed_url = parse_url($file);
            $filename = basename($parsed_url['path']);
            $this->unlinkFile(public_path($filename));
            return true;
        } else {
            $this->unlinkFile(public_path($file));
            return true;
        }
    }

    private function unlinkFile($file)
    {
        if (file_exists($file)) {
            unlink($file);
        }
    }

    /**
     * return a response for auth user
     */
    protected function sendAuthResponse($user, $msg)
    {
        Auth::login($user);

        // Clear token after successful login
        $user->clearLoginToken();

        // pass user role
        $user->role = $user->app_role ?? 'Member';

        // create auth token
        $token = $user->createToken('project_mngt_token')->plainTextToken;
        return $this->sendResponse(
            result: $user, 
            message: $msg,
            code: 201,
            token: [
                'token' => $token,
                'loginAt' => now()->timestamp,
            ]
        );
    }
}
