<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PermissionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        // Derive resource and action from slug (e.g., users.view -> resource: users, action: view)
        $parts = explode('.', $this->slug);
        $resource = $parts[0] ?? null;
        $action = $parts[1] ?? null;

        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'resource' => $resource,
            'action' => $action,
        ];
    }
}
