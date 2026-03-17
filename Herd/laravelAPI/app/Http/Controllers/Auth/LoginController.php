<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::where('email', $validated['email'])->first();

        if (! $user) {
            return response()->json([
                'message' => 'Invalid credentials.',
            ], 401);
        }

        if ($user->status !== 'active') {
            return response()->json([
                'message' => 'Your account is inactive.',
            ], 403);
        }

        if (! Auth::attempt($validated, $request->boolean('remember'))) {
            return response()->json([
                'message' => 'Invalid credentials.',
            ], 401);
        }

        $request->session()->regenerate();

        return response()->json([
            'message' => 'Login successful.',
            'user' => new UserResource(Auth::user()),
        ]);
    }
}