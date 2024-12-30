<?php

namespace App\Http\Controllers\Api\v1\Auth;

use App\Enums\GenderEnum;
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
     * @OA\Post(
     *     path="/api/v1/register",
     *     summary="Register a new user",
     *     tags={"Auth"},
     *     description="Registers a new user and returns an authentication token.",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"first_name", "last_name", "email", "phone_number", "id_type", "id_number", "password", "role"},
     *             @OA\Property(property="first_name", type="string", example="John"),
     *             @OA\Property(property="last_name", type="string", example="Doe"),
     *             @OA\Property(property="email", type="string", format="email", example="john.doe@example.com"),
     *             @OA\Property(property="phone_number", type="string", example="123-456-7890"),
     *             @OA\Property(property="id_type", type="string", enum={"passport", "national_id"}, example="passport"),
     *             @OA\Property(property="id_number", type="string", example="AB123456"),
     *             @OA\Property(property="password", type="string", format="password", example="strongpassword123"),
     *             @OA\Property(property="password_confirmation", type="string", format="password", example="strongpassword123"),
     *             @OA\Property(property="role", type="string", enum={"super_admin", "platform_admin", "parent", "child"}, example="parent")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="User successfully registered",
     *         @OA\JsonContent(
     *             @OA\Property(property="token", type="string", example="eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation errors",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="The given data was invalid."),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     )
     * )
     */
    public function store(Request $request)
    {
        $request->validate([
            'first_name'   => ['required', 'string', 'max:255'],
            'last_name'    => ['required', 'string', 'max:255'],
            'email'        => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:' . User::class],
            'phone_number' => ['required', 'string', 'max:255'],
            'id_type'      => ['required', new Rules\Enum(IdTypeEnum::class)],
            'id_number'    => ['required', 'string', 'max:255'],
            'password'     => ['required', 'confirmed', Rules\Password::defaults()],
            'role'         => ['required', new Rules\Enum(RoleEnum::class)],
        ]);

        $user = User::create([
            'email'    => $request->email,
            'password' => Hash::make($request->string('password')),
        ]);

        $user->profile()->create([
            'first_name'   => $request->first_name,
            'last_name'    => $request->last_name,
            'phone_number' => $request->phone_number,
            'id_type'      => $request->id_type,
            'id_number'    => $request->id_number,
        ]);

        $token = $user->createToken($request->userAgent())->plainTextToken;

        return response()->json(['token' => $token], Response::HTTP_CREATED);
    }

    /**
     * @OA\Post(
     *     path="/api/v1/invite-register",
     *     summary="Register a user from an invitation",
     *     tags={"Auth"},
     *     description="Registers a new user using an invitation code and returns an authentication token.",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"code", "first_name", "last_name", "id_type", "id_number", "password", "password_confirmation"},
     *             @OA\Property(property="code", type="string", description="Invitation code", example="INV12345"),
     *             @OA\Property(property="first_name", type="string", description="User's first name", example="John"),
     *             @OA\Property(property="last_name", type="string", description="User's last name", example="Doe"),
     *             @OA\Property(property="id_type", type="string", enum={"passport", "emirates"}, description="Type of identification", example="passport"),
     *             @OA\Property(property="id_number", type="string", description="Identification number", example="A12345678"),
     *             @OA\Property(property="password", type="string", format="password", description="User's password", example="securePassword123"),
     *             @OA\Property(property="password_confirmation", type="string", format="password", description="Password confirmation", example="securePassword123")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="User successfully registered from invitation",
     *         @OA\JsonContent(
     *             @OA\Property(property="token", type="string", example="eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Invitation not found or expired",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Invitation not found or expired.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation errors",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="The given data was invalid."),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     )
     * )
     */
    public function invitation(Request $request)
    {
        $request->validate([
            'code'       => ['required', 'string', 'exists:invitations,code'],
            'first_name' => ['required', 'string', 'max:255'],
            'last_name'  => ['required', 'string', 'max:255'],
            'id_type'    => ['required', new Rules\Enum(IdTypeEnum::class)],
            'id_number'  => ['required', 'string', 'max:255'],
            'password'   => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $invitation = Invitation::where('code', $request->code)
            ->whereNull('expired_at')
            ->firstOrFail();

        $user = User::create([
            'email'     => $invitation->email,
            'password'  => Hash::make($request->input('password')),
            'family_id' => $invitation->family_id,
        ]);

        $user->profile()->create([
            'first_name' => $request->name,
            'last_name'  => $request->surname,
        ]);

        $invitation->update(['expired_at' => now()]);

        $token = $user->createToken($request->userAgent())->plainTextToken;

        return response()->json(['token' => $token], Response::HTTP_CREATED);
    }
}
