<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function update(Request $request)
    {
        $user = auth()->guard('sanctum')->user();

        $request->validate([
            'email' => ['sometimes', 'unique:users,email'],
            'phone_number' => ['sometimes', 'unique:profiles,phone_number']
        ]);

        if ($request->filled('email')) {
            $user->update(['email' => $request->get('email')]);
        }

        if ($request->filled('phone_number')) {
            $user->profile->update(['phone_number' => $request->get('phone_number')]);
        }

        return response()->json($user->load('profile'));
    }
}
