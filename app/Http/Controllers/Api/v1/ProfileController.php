<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\v1\UserResource;
use DB;
use Illuminate\Http\Request;
use Throwable;

class ProfileController extends Controller
{
    public function update(Request $request)
    {
        $user = auth()->guard('sanctum')->user();

        $request->validate([
            'email'        => ['sometimes', 'unique:users,email'],
            'phone_number' => ['sometimes', 'unique:profiles,phone_number']
        ]);

        if ($request->filled('email')) {
            $user->update(['email' => $request->get('email')]);
        }

        if ($request->filled('phone_number')) {
            $user->profile->update(['phone_number' => $request->get('phone_number')]);
        }

        return new UserResource($user->load('profile', 'roles.permissions'));
    }

    public function destroy(Request $request)
    {
        $user = auth()->guard('sanctum')->user();

        $request->validate([
            'password' => ['required', 'current_password']
        ]);

        try {
            DB::transaction(function () use ($user) {
                $user->tokens()->delete();

                if ($user->profile) {
                    $user->profile->delete();
                }

                $user->delete();
            });

        } catch (Throwable $exception) {
            return response()->json(['message' => "An error occurred while deleting the account. Exception: {$exception->getMessage()}"], 500);
        }


        return response()->json(['message' => 'Account deleted successfully.']);
    }
}
