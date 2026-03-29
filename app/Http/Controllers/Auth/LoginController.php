<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class LoginController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $email = $request->input('email');
        $password = $request->input('password');

        $validated = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::where('email', $validated['email'])->first();

        if (! $user) {
            return response()->json([
                'message' => 'Invalid credentials.',
                'debug' => ['user_found' => false, 'email' => $email],
            ], 401);
        }

        if ($user->status !== 'active') {
            return response()->json([
                'message' => 'Your account is inactive.',
            ], 403);
        }

        // Debug: Return hash info for analysis
        $debugInfo = [
            'user_found' => true,
            'email' => $user->email,
            'status' => $user->status,
            'stored_hash_first_10' => substr($user->password, 0, 10),
            'input_password_first_10' => substr($validated['password'], 0, 10),
            'hash_algorithm' => str_starts_with($user->password, '$2y$') ? 'bcrypt' : 'unknown',
        ];

        if (! Hash::check($validated['password'], $user->password)) {
            return response()->json([
                'message' => 'Invalid credentials.',
                'debug' => $debugInfo,
            ], 401);
        }

        $token = $user->createToken('login', ['*']);

        return response()->json([
            'message' => 'Login successful.',
            'user' => new UserResource($user),
            'token' => $token->plainTextToken,
        ]);
    }
}