<?php

namespace App\Http\Controllers\Api\v1\Auth;

use App\Http\Controllers\Controller;
use App\Models\OtpCode;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class NewPasswordController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $user = User::with(['profile' => function ($query) use ($request) {
            $query->where('phone_number', $request->input('phone_number'));
        }])->firstOrFail();

        $user->update([
            'password' => Hash::make($request->input('password'))
        ]);

        return response()->json(['message' => 'Password reset successfully.']);
    }
}
