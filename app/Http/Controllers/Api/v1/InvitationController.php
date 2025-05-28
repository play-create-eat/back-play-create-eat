<?php

namespace App\Http\Controllers\Api\v1;

use App\Enums\IdTypeEnum;
use App\Enums\Otps\PurposeEnum;
use App\Enums\Otps\StatusEnum;
use App\Enums\Otps\TypeEnum;
use App\Enums\PartialRegistrationStatusEnum;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\v1\UserResource;
use App\Models\Invitation;
use App\Models\OtpCode;
use App\Models\PartialRegistration;
use App\Models\User;
use App\Services\OtpService;
use App\Services\TwilloService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Symfony\Component\HttpFoundation\Response;

class InvitationController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/v1/invite",
     *     summary="Invite a family member",
     *     tags={"Invite"},
     *     description="Sends an invitation to join the user's family group via email.",
     *     security={{"sanctum": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email"},
     *             @OA\Property(property="email", type="string", format="email", description="Email address of the invitee", example="invitee@example.com")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Invitation sent successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Invitation sent successfully.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="User is not part of a family",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="User is not part of a family.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation errors",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="The given data was invalid."),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthenticated.")
     *         )
     *     )
     * )
     */
    public function invite(Request $request, OtpService $otpService, TwilloService $twilloService)
    {
        $request->validate([
            'phone_number'  => ['required', Rule::unique('profiles')->whereNull('deleted_at')],
            'role'          => ['required', 'exists:roles,name'],
            'permissions'   => ['nullable', 'array'],
            'permissions.*' => ['sometimes', 'exists:permissions,name']
        ]);

        $user = auth()->guard('sanctum')->user();
        $familyId = $user->family_id;

        if (!$familyId) {
            return response()->json(['message' => 'User is not part of a family.'], 400);
        }

        $code = rand(1000, 9999);
//        $code = 1234;

        $invite = Invitation::create([
            'code'         => $code,
            'phone_number' => $request->get('phone_number'),
            'role'         => $request->get('role'),
            'permissions'  => $request->get('permissions'),
            'family_id'    => $familyId,
            'created_by'   => $user->id,
            'expires_at'   => now()->addDay()
        ]);

        $message = "You’re invited to join PlayCreateEat: \nPlease use this link to join: play-create-eat://invite/$code \nOr set this code on register: $code";
        $twilloService->sendSms($invite->phone_number, $message);

        return response()->json([
            'message' => 'Invitation sent successfully.',
            'code'    => $code
        ]);
    }

    public function validateStep1(Request $request)
    {
        $validated = $request->validate([
            'code'       => ['required', 'exists:invitations,code'],
            'first_name' => ['required', 'string', 'max:255'],
            'last_name'  => ['required', 'string', 'max:255'],
        ]);

        $invitation = Invitation::where('code', $request->get('code'))
            ->where('used', false)
            ->firstOrFail();

        $familyId = User::find($invitation->created_by)->family_id;
        $partialRegistration = PartialRegistration::create([...$validated, 'family_id' => $familyId]);

        return response()->json([
            'message'         => 'Step 1 completed successfully.',
            'registration_id' => $partialRegistration->id,
        ], Response::HTTP_CREATED);
    }

    public function validateStep2(Request $request, OtpService $otpService, TwilloService $twilloService)
    {
        $request->validate([
            'code'            => ['required', 'exists:invitations,code'],
            'registration_id' => ['required', 'string', 'uuid', 'exists:partial_registrations,id'],
            'email'           => ['required', 'string', 'email', 'max:255', Rule::unique('users')->whereNull('deleted_at')],
            'phone_number'    => ['required', 'string', 'max:255', Rule::unique('profiles')->whereNull('deleted_at')],
            'password'        => ['required', 'confirmed', Password::defaults()],
        ]);

        $partialRegistration = PartialRegistration::findOrFail($request->get('registration_id'));

        $partialRegistration->update([
            'email'        => $request->get('email'),
            'phone_number' => $request->get('phone_number'),
            'password'     => Hash::make($request->get('password')),
            'status'       => PartialRegistrationStatusEnum::Completed,
        ]);

        $otpCode = $otpService->generate(null, TypeEnum::PHONE, PurposeEnum::REGISTER, $partialRegistration->phone_number);
        $otpService->send($otpCode);

        return response()->json([
            'message'         => 'Step 2 completed successfully.',
            'registration_id' => $partialRegistration->id,
        ], Response::HTTP_CREATED);
    }

    public function register(Request $request)
    {
        $request->validate([
            'code'            => ['required', 'exists:invitations,code'],
            'registration_id' => ['required', 'exists:partial_registrations,id'],
        ]);

        $partialRegistration = PartialRegistration::findOrFail($request->get('registration_id'));

        $otp = OtpCode::where('identifier', $partialRegistration->phone_number)
            ->where('status', StatusEnum::VERIFIED)
            ->first();

        if (!$otp) {
            return response()->json(['message' => 'Invalid OTP.'], 422);
        }

        $user = User::create([
            'email'     => $partialRegistration->email,
            'password'  => $partialRegistration->password,
            'family_id' => $partialRegistration->family_id,
        ]);

        $user->profile()->create([
            'first_name'   => $partialRegistration->first_name,
            'last_name'    => $partialRegistration->last_name,
            'phone_number' => $partialRegistration->phone_number,
        ]);

        $invitation = Invitation::where('code', $request->get('code'))->firstOrFail();

        $user->givePermissionTo($invitation->permissions);
        $user->assignRole($invitation->role);
        $invitation->update(['used' => true]);

        $partialRegistration->delete();

        $token = $user->createToken($request->userAgent())->plainTextToken;

        return response()->json([
            'token' => $token,
            'user'  => new UserResource($user->load(['profile', 'family', 'roles.permissions']))
        ]);
    }
}
