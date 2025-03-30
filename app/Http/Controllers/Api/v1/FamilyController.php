<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\v1\FamilyPassResource;
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

    public function passes()
    {
        $family = auth()->guard('sanctum')->user()->family;
        $passes = $family->passes()->with(['children', 'transfer.deposit'])->paginate(20);

        return FamilyPassResource::collection($passes);
    }
}
