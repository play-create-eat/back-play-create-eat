<?php

namespace App\Http\Controllers\Api\v1\Internal;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\v1\Internal\AdminUserResource;
use App\Http\Resources\Api\v1\UserResource;
use App\Models\Admin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpFoundation\Response;

class AuthenticatedSessionController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/v1/internal/login",
     *     summary="Login a internal user",
     *     tags={"InternalAuth"},
     *     description="Logs in a internal user and returns an authentication token.",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email", "password"},
     *             @OA\Property(property="email", type="string", description="User's email address", example="xyz@company.com"),
     *             @OA\Property(property="password", type="string", format="password", description="Internal user's password", example="StrongPassword123")
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
     *                 @OA\Property(property="updated_at", type="string", format="date-time", example="2021-01-01T00:00:00Z")
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
            'email'     => ['required', 'email'],
            'password'  => ['required'],
        ]);

        $user = Admin::where('email', $request->input('email'))->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Invalid credentials provided.'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $user->tokens()->delete();
        $token = $user->createToken($request->userAgent())->plainTextToken;

        return response()->json([
            'token' => $token,
            'user'  => new AdminUserResource($user)
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/v1/internal/logout",
     *     summary="Log out a internal user",
     *     tags={"InternalAuth"},
     *     description="Logs out the authenticated internal user by deleting their current access token.",
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
        auth()->guard('internal-api')->user()->currentAccessToken()->delete();
        return response()->noContent();
    }
}
