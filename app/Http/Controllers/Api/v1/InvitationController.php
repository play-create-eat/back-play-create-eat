<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Mail\InviteMemberMail;
use App\Models\Invitation;
use Illuminate\Http\Request;
use Mail;

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
    public function invite(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email', 'unique:users,email'],
        ]);

        $user = $request->user();
        $familyId = $user->family_id;

        if (!$familyId) {
            return response()->json(['message' => 'User is not part of a family.'], 400);
        }

        $code = rand(100000, 999999);

        Invitation::create([
           'code' => $code,
           'email' => $request->email,
           'family_id' => $familyId,
           'creator_id' => $request->user()->id,
        ]);

        Mail::to($request->email)->send(new InviteMemberMail($code));

        return response()->json(['message' => 'Invitation sent successfully.']);
    }
}
