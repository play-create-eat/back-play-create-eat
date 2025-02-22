<?php

namespace App\Http\Resources\Api\v1;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'      => $this->id,
            'email'   => $this->email,
            'profile' => new ProfileResource($this->whenLoaded('profile')),
            'family'  => new FamilyResource($this->whenLoaded('family')),
            'roles'   => $this->roles->map(function ($role) {
                return [
                    'name'        => $role->name,
                    'permissions' => $role->permissions->pluck('name'), ...$this->getPermissionNames(),
                ];
            }),
            'created_at' => Carbon::parse($this->created_at)->toIso8601String()
        ];
    }
}
