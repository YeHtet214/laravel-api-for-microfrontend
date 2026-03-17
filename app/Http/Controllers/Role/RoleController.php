<?php

namespace App\Http\Controllers\Role;

use App\Http\Controllers\Controller;
use App\Http\Requests\Role\StoreRoleRequest;
use App\Http\Requests\Role\UpdateRoleRequest;
use App\Http\Resources\RoleResource;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class RoleController extends Controller
{
    /**
     * Display a listing of the roles.
     */
    public function index(Request $request)
    {
        $query = Role::withCount('permissions');

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('slug', 'like', "%{$search}%");
            });
        }

        if ($request->boolean('dropdown')) {
            return RoleResource::collection($query->get());
        }

        return RoleResource::collection($query->paginate($request->integer('per_page', 15)));
    }

    /**
     * Store a newly created role in storage.
     */
    public function store(StoreRoleRequest $request)
    {
        $role = Role::create($request->validated());
        
        if ($request->has('permission_ids')) {
            $role->permissions()->sync($request->permission_ids);
        }

        return new RoleResource($role->load('permissions'));
    }

    /**
     * Display the specified resource.
     */
    public function show(Role $role)
    {
        return new RoleResource($role->load('permissions'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateRoleRequest $request, Role $role)
    {
        $role->update($request->validated());

        if ($request->has('permission_ids')) {
            $role->permissions()->sync($request->permission_ids);
        }

        return new RoleResource($role->load('permissions'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Role $role)
    {
        // Prevent deleting critical roles first
        if (in_array($role->slug, ['admin'])) {
            return response()->json([
                'message' => 'Cannot delete business-critical roles.'
            ], Response::HTTP_FORBIDDEN);
        }

        if ($role->users()->exists()) {
            return response()->json([
                'message' => 'Cannot delete role that is currently assigned to users.'
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $role->delete();

        return response()->json(['message' => 'Role deleted successfully.']);
    }
}
