<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ChildResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,
            'first_name' => $this->first_name,
            'last_name'  => $this->last_name,
            'birth_date' => $this->birth_date,
            'gender'     => $this->gender,
            'family_id'  => $this->family_id,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'avatar'     => $this->getFirstMediaUrl('child_avatars'),
        ];
    }
}
