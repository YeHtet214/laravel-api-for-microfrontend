<?php

namespace App\Http\Controllers\Permission;

use App\Http\Controllers\Controller;
use App\Http\Resources\PermissionResource;
use App\Models\Permission;
use Illuminate\Http\Request;

class PermissionController extends Controller
{
    /**
     * Display a listing of permissions grouped by resource.
     */
    public function index()
    {
        $permissions = Permission::all();

        $grouped = $permissions->groupBy(function ($permission) {
            $parts = explode('.', $permission->slug);
            return $parts[0] ?? 'general';
        })->map(function ($group, $resource) {
            return [
                'resource' => $resource,
                'label' => ucfirst($resource) . ' Management',
                'permissions' => PermissionResource::collection($group),
            ];
        })->values();

        return response()->json([
            'data' => $grouped
        ]);
    }
}
