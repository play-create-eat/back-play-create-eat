<?php

namespace App\Http\Controllers\Api\v1\Auth;

use App\Enums\IdTypeEnum;
use App\Enums\Otps\PurposeEnum;
use App\Enums\Otps\TypeEnum;
use App\Enums\PartialRegistrationStatusEnum;
use App\Http\Controllers\Controller;
use App\Models\Family;
use App\Models\Invitation;
use App\Models\PartialRegistration;
use App\Models\User;
use App\Services\OtpService;
use App\Services\TwilloService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\Rules\Password;
use Symfony\Component\HttpFoundation\Response;

class RegisteredUserController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/v1/register/step-1",
     *     summary="Start registration - Step 1",
     *     tags={"Auth"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"first_name", "last_name", "id_type", "id_number"},
     *             @OA\Property(property="first_name", type="string", maxLength=255, example="John"),
     *             @OA\Property(property="last_name", type="string", maxLength=255, example="Doe"),
     *             @OA\Property(property="id_type", type="string", example="passport"),
     *             @OA\Property(property="id_number", type="string", maxLength=255, example="123456789"),
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Step 1 completed successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Step 1 completed successfully."),
     *             @OA\Property(property="registration_id", type="string", format="uuid", example="123e4567-e89b-12d3-a456-426614174000")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="The given data was invalid."),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     )
     * )
     */
    public function step1(Request $request)
    {
        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name'  => ['required', 'string', 'max:255'],
            'id_type'    => ['required', new Enum(IdTypeEnum::class)],
            'id_number'  => ['required', 'string', 'max:255', 'unique:profiles'],
        ]);

        $family = Family::create([
            'name' => "{$validated['last_name']}'s Family",
        ]);

        $partialRegistration = PartialRegistration::create([...$validated, 'family_id' => $family->id]);

        return response()->json([
            'message'         => 'Step 1 completed successfully.',
            'registration_id' => $partialRegistration->id,
        ], Response::HTTP_CREATED);

    }

    /**
     * @OA\Post(
     *     path="/api/v1/register/step-2",
     *     summary="Complete registration - Step 2",
     *     tags={"Auth"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"registration_id", "email", "phone_number", "password", "password_confirmation"},
     *             @OA\Property(property="registration_id", type="string", format="uuid", example="123e4567-e89b-12d3-a456-426614174000"),
     *             @OA\Property(property="email", type="string", format="email", example="john.doe@example.com"),
     *             @OA\Property(property="phone_number", type="string", example="1234567890"),
     *             @OA\Property(property="password", type="string", format="password", example="StrongPassword123"),
     *             @OA\Property(property="password_confirmation", type="string", format="password", example="StrongPassword123")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Registration completed successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Step 2 completed successfully."),
     *             @OA\Property(property="registration_id", type="string", format="uuid", example="123e4567-e89b-12d3-a456-426614174000")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="The given data was invalid."),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     )
     * )
     * @throws \Exception
     */
    public function step2(Request $request, OtpService $otpService, TwilloService $twilloService)
    {
        $validated = $request->validate([
            'registration_id' => ['required', 'exists:partial_registrations,id'],
            'email'           => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'phone_number'    => ['required', 'string', 'max:255'],
            'password'        => ['required', 'confirmed', Password::defaults()],
        ]);

        $partialRegistration = PartialRegistration::findOrFail($validated['registration_id']);

        $partialRegistration->update([
            'email'        => $validated['email'],
            'phone_number' => $validated['phone_number'],
            'password'     => Hash::make($validated['password']),
            'status'       => PartialRegistrationStatusEnum::Completed,
        ]);

        $otpCode = $otpService->generate(null, TypeEnum::PHONE, PurposeEnum::REGISTER, $partialRegistration->phone_number);
        $otpService->send($otpCode, $twilloService);

        return response()->json([
            'message'         => 'Step 2 completed successfully.',
            'registration_id' => $partialRegistration->id,
        ], Response::HTTP_CREATED);
    }

    /**
     * @OA\Post(
     *     path="/api/v1/register",
     *     summary="Complete registration",
     *     tags={"Auth"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"registration_id", "password"},
     *             @OA\Property(property="registration_id", type="string", format="uuid", example="123e4567-e89b-12d3-a456-426614174000"),
     *             @OA\Property(property="password", type="string", format="password", example="StrongPassword123")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Registration completed successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="token", type="string", example="eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9..."),
     *             @OA\Property(property="user", type="object", properties={
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="John Doe"),
     *                 @OA\Property(property="email", type="string", format="email", example="john.doe@example.com"),
     *                 @OA\Property(property="created_at", type="string", format="date-time", example="2021-01-01T00:00:00Z"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time", example="2021-01-01T00:00:00Z"),
     *                 @OA\Property(property="profile", type="object", properties={
     *                     @OA\Property(property="first_name", type="string", example="John"),
     *                     @OA\Property(property="last_name", type="string", example="Doe"),
     *                     @OA\Property(property="phone_number", type="string", example="1234567890"),
     *                     @OA\Property(property="id_type", type="string", example="passport"),
     *                     @OA\Property(property="id_number", type="string", example="123456789"),
     *                     @OA\Property(property="created_at", type="string", format="date-time", example="2021-01-01T00:00:00Z"),
     *                     @OA\Property(property="updated_at", type="string", format="date-time", example="2021-01-01T00:00:00Z")
     *                 }),
     *                 @OA\Property(property="family", type="object", properties={
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="Doe's Family"),
     *                     @OA\Property(property="created_at", type="string", format="date-time", example="2021-01-01T00:00:00Z"),
     *                     @OA\Property(property="updated_at", type="string", format="date-time", example="2021-01-01T00:00:00Z")
     *                 })
     *             })
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="The given data was invalid."),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     )
     * )
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'registration_id' => ['required', 'exists:partial_registrations,id'],
        ]);

        $partialRegistration = PartialRegistration::findOrFail($validated['registration_id']);

        $user = User::create([
            'email'     => $partialRegistration->email,
            'password'  => Hash::make($request->string('password')),
            'family_id' => $partialRegistration->family_id,
        ]);

        $user->profile()->create([
            'first_name'   => $partialRegistration->first_name,
            'last_name'    => $partialRegistration->last_name,
            'phone_number' => $partialRegistration->phone_number,
            'id_type'      => $partialRegistration->id_type,
            'id_number'    => $partialRegistration->id_number,
        ]);

        $partialRegistration->delete();

        $token = $user->createToken($request->userAgent())->plainTextToken;

        return response()->json(['token' => $token, 'user' => $user->load(['profile', 'family'])], Response::HTTP_CREATED);
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
