<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Enums\IdTypeEnum;
use App\Enums\RoleEnum;
use App\Http\Controllers\Controller;
use App\Models\Invitation;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

class RegisteredUserController extends Controller
{
    /**
     * Handle an incoming registration request.
     *
     * @throws ValidationException
     */
    public function store(Request $request)
    {
        $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'phone_number' => ['required', 'string', 'max:255'],
            'id_type' => ['required', new Rules\Enum(IdTypeEnum::class)],
            'id_number' => ['required', 'string', 'max:255'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'role' =>  ['required', new Rules\Enum(RoleEnum::class)],
        ]);

        $user = User::create([
            'email' => $request->email,
            'password' => Hash::make($request->string('password')),
        ]);

        $user->profile()->create([
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'phone_number' => $request->phone_number,
            'id_type' => $request->id_type,
            'id_number' => $request->id_number,
        ]);

        $token = $user->createToken($request->userAgent())->plainTextToken;

        return response()->json(['token' => $token], Response::HTTP_CREATED);
    }

    public function invitation(Request $request) {
        $request->validate([
            'code' => ['required', 'string', 'exists:invitations,code'],
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'id_type' => ['required', new Rules\Enum(IdTypeEnum::class)],
            'id_number' => ['required', 'string', 'max:255'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $invitation = Invitation::where('code', $request->code)
            ->whereNull('expired_at')
            ->firstOrFail();

        $user = User::create([
            'email' => $invitation->email,
            'password' => Hash::make($request->input('password')),
            'family_id' => $invitation->family_id,
        ]);

        $user->profile()->create([
            'first_name' => $request->name,
            'last_name' => $request->surname,
        ]);

        $invitation->update(['expired_at' => now()]);

        $token = $user->createToken($request->userAgent())->plainTextToken;

        return response()->json(['token' => $token], Response::HTTP_CREATED);
    }
}
