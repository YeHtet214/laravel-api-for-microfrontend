<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RoleResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'permissions' => PermissionResource::collection($this->whenLoaded('permissions')),
            'permissions_count' => $this->whenCounted('permissions'),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
