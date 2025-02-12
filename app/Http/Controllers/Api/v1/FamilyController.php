<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\v1\UserResource;
use App\Models\User;

class FamilyController extends Controller
{
    public function members()
    {
        $family = auth()->guard('sanctum')->user()->family;

        $members = User::with(['family', 'profile', 'roles', 'roles.permissions'])
            ->where('family_id', $family->id)->get();

        return UserResource::collection($members);

    }
}
