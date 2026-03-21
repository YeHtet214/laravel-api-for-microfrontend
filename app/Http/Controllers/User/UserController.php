<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Http\Requests\UpdateUserStatusRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $search = $request->string('search')->toString();
        $status = $request->string('status')->toString();
        $perPage = (int) $request->input('per_page', 10);

        $perPage = $perPage > 0 ? min($perPage, 100) : 10;

        $query = User::with('roles');

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if (!empty($status)) {
            $query->where('status', $status);
        }

        $users = $query
            ->latest()
            ->paginate($perPage)
            ->withQueryString();

        return response()->json([
            'message' => 'Users retrieved successfully',
            'data' => UserResource::collection($users)->response()->getData(true),
        ]);
    }

    public function show(User $user): JsonResponse
    {
        return response()->json([
            'message' => 'User retrieved successfully',
            'user' => new UserResource($user->load('roles')),
        ]);
    }

    public function store(StoreUserRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $validated['password'] = Hash::make($validated['password']);

        $user = User::create($validated);
        
        if ($request->has('role_id')) {
            $user->assignRole($request->role_id);
        }

        return response()->json([
            'message' => 'User created successfully',
            'user' => new UserResource($user->load('roles')),
        ], 201);
    }

    public function update(UpdateUserRequest $request, User $user): JsonResponse
    {
        $validated = $request->validated();

        if (!empty($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        $user->update($validated);

        if ($request->has('role_id')) {
            $user->assignRole($request->role_id);
        }

        return response()->json([
            'message' => 'User updated successfully',
            'user' => new UserResource($user->fresh('roles')),
        ]);
    }

    public function updateStatus(UpdateUserStatusRequest $request, User $user): JsonResponse
    {
        $validated = $request->validated();

        $user->update([
            'status' => $validated['status'],
        ]);

        return response()->json([
            'message' => 'User status updated successfully',
            'user' => new UserResource($user->fresh('roles')),
        ]);
    }
}
