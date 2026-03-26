<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function getAuthUser(Request $request)
    {
        $user = $request->user();

        if ($user->status !== "active") {
            if (config('auth.defaults.guard') === 'web') {
                Auth::guard('web')->logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();
            }

            return response()->json([
                'message' => 'Your account is inactive.',
            ], 403);
        }

        $role = $user->roles->first();
        $permissions = $user->roles->flatMap(function ($role) {
            return $role->permissions->pluck('slug');
        })->unique()->values();

        return response()->json([
            'message' => 'Authenticated user retrieved successfully',
            'user' => new UserResource($user),
            'role' => $role ? [
                'id' => $role->id,
                'name' => $role->name,
                'slug' => $role->slug,
            ] : null,
            'permissions' => $permissions,
        ]);
    }

    public function logout(Request $request)
    {
        if ($token = $request->user()?->currentAccessToken()) {
            $token->delete();
        }

        if (Auth::guard('web')->check()) {
            Auth::guard('web')->logout();
        }

        if ($request->hasSession()) {
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        return response()->json([
            'message' => 'Logged out successfully.',
        ]);
    }
}
