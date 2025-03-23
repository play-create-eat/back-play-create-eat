<?php

namespace App\Http\Resources\Api\v1\Internal;

use App\Http\Resources\Api\v1\FamilyPassResource;
use Carbon\Carbon;
use Illuminate\Http\Request;

class PassResource extends FamilyPassResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            ...parent::toArray($request),
            'entered_at' => $this->entered_at ? Carbon::parse($this->entered_at)->toIso8601String() : null,
            'exited_at' => $this->exited_at ? Carbon::parse($this->exited_at)->toIso8601String() : null,
        ];
    }
}
