<?php

namespace App\Http\Controllers\Api\v1\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpFoundation\Response;

class AuthenticatedSessionController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/v1/login",
     *     summary="Login a user",
     *     tags={"Auth"},
     *     description="Logs in a user and returns an authentication token.",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"phone_number", "password"},
     *             @OA\Property(property="phone_number", type="string", description="User's phone number", example="1234567890"),
     *             @OA\Property(property="password", type="string", format="password", description="User's password", example="StrongPassword123")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="User successfully logged in",
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
     *         description="Invalid credentials",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Invalid credentials provided.")
     *         )
     *     )
     * )
     */
    public function store(Request $request)
    {
        $request->validate([
            'phone_number'    => ['required', 'exists:profiles,phone_number'],
            'password' => ['required'],
        ]);

        $user = User::whereHas('profile', function ($query) use ($request) {
            $query->where('phone_number', $request->input('phone_number'));
        })->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Invalid credentials provided.'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $user->tokens()->delete();

        $token = $user->createToken($request->userAgent())->plainTextToken;

        return response()->json(['token' => $token, 'user' => $user->load(['profile', 'family'])]);
    }

    /**
     * @OA\Post(
     *     path="/api/auth/logout",
     *     summary="Log out a user",
     *     tags={"Auth"},
     *     description="Logs out the authenticated user by deleting their current access token.",
     *     security={{"sanctum": {}}},
     *     @OA\Response(
     *         response=204,
     *         description="Successfully logged out"
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
    public function destroy()
    {
        auth()->guard('sanctum')->user()->currentAccessToken()->delete();

        return response()->noContent();
    }
}
