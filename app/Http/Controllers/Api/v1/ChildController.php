<?php

namespace App\Http\Controllers\Api\v1;

use App\Enums\GenderEnum;
use App\Http\Controllers\Controller;
use App\Http\Resources\ChildResource;
use App\Models\Child;
use App\Models\PartialRegistration;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Enum;
use Symfony\Component\HttpFoundation\Response;

/**
 * @OA\Schema(
 *     schema="ChildResource",
 *     type="object",
 *     title="Child Resource",
 *     description="Child resource object",
 *     @OA\Property(property="id", type="integer", example=1, description="Child ID"),
 *     @OA\Property(property="first_name", type="string", example="John", description="First name of the child"),
 *     @OA\Property(property="last_name", type="string", example="Doe", description="Last name of the child"),
 *     @OA\Property(property="birth_date", type="string", format="date", example="2015-06-15", description="Birth date of the child"),
 *     @OA\Property(property="gender", type="string", example="male", description="Gender of the child"),
 *     @OA\Property(property="family_id", type="integer", example=5, description="Family ID associated with the child"),
 *     @OA\Property(property="created_at", type="string", format="date-time", example="2024-01-06T12:34:56Z", description="Timestamp of creation"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", example="2024-01-06T12:34:56Z", description="Timestamp of last update"),
 *     @OA\Property(property="avatar", type="string", format="url", example="https://example.com/avatars/avatar1.png", description="Avatar URL of the child")
 * )
 */
class ChildController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/v1/children",
     *     summary="Get all children of the authenticated user's family",
     *     tags={"Children"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="registration_id",
     *         in="query",
     *         required=false,
     *         description="Registration ID from registered parent",
     *         @OA\Schema(type="string", format="uuid", example="123e4567-e89b-12d3-a456-426614174000")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="List of children",
     *         @OA\JsonContent(type="array", @OA\Items(ref="#/components/schemas/ChildResource"))
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         @OA\JsonContent(@OA\Property(property="message", type="string", example="Unauthenticated."))
     *     )
     * )
     */
    public function index(Request $request)
    {
        if ($request->input('registration_id')) {
            $familyId = PartialRegistration::findOrFail($request->registration_id)->family_id;
            return response()->json(ChildResource::collection(Child::where('family_id', $familyId)->get()));
        }

        $familyChildren = auth()->guard('sanctum')->user()->family->children;
        return response()->json(ChildResource::collection($familyChildren));
    }

    /**
     * @OA\Get(
     *     path="/api/v1/children/{id}",
     *     summary="Get details of a specific child",
     *     tags={"Children"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID of the child",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Details of the child",
     *         @OA\JsonContent(ref="#/components/schemas/ChildResource")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Child not found",
     *         @OA\JsonContent(@OA\Property(property="message", type="string", example="Child not found."))
     *     )
     * )
     */
    public function show(Child $child)
    {
        return new ChildResource($child);
    }

    /**
     * @OA\Post(
     *     path="/api/v1/children",
     *     summary="Create a new child",
     *     tags={"Children"},
     *     security={{"sanctum": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             required={"first_name", "last_name", "birth_date", "gender"},
     *             @OA\Property(property="first_name", type="string", maxLength=255, example="John", description="First name of the child"),
     *             @OA\Property(property="last_name", type="string", maxLength=255, example="Doe", description="Last name of the child"),
     *             @OA\Property(property="birth_date", type="string", format="date", example="2015-06-15", description="Birth date of the child in YYYY-MM-DD format"),
     *             @OA\Property(
     *                 property="gender",
     *                 type="string",
     *                 enum={"male", "female", "other"},
     *                 example="male",
     *                 description="Gender of the child (male, female, or other)"
     *             ),
     *             @OA\Property(property="registration_id", type="string", format="uuid", example="123e4567-e89b-12d3-a456-426614174000", description="Optional Registration ID from registered parent")
     *             @OA\Property(
     *                  property="avatar",
     *                  type="string",
     *                  format="binary",
     *                  description="Optional avatar image for the child, max size 10MB"
     *              )
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Child created successfully",
     *         @OA\JsonContent(ref="#/components/schemas/ChildResource")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="The given data was invalid."),
     *             @OA\Property(property="errors", type="object", description="An object containing validation errors")
     *         )
     *     )
     * )
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'first_name'      => ['required', 'string', 'max:255'],
            'last_name'       => ['required', 'string', 'max:255'],
            'birth_date'      => ['required', 'date', 'before:today'],
            'gender'          => ['required', 'string', new Enum(GenderEnum::class)],
            'registration_id' => ['sometimes', 'string', 'exists:partial_registrations,id'],
            'avatar'          => ['sometimes', 'image', 'max:10240'],
        ]);

        $familyId = $request->input('registration_id')
            ? PartialRegistration::find($validated['registration_id'])->family_id
            : auth()->guard('sanctum')->user()->family->id;

        $child = Child::create([...$validated, 'family_id' => $familyId]);

        if ($request->hasFile('avatar')) {
            $child->addMedia($request->file('avatar'))->toMediaCollection('avatars');
        }

        return response()->json(new ChildResource($child), Response::HTTP_CREATED);
    }

    /**
     * @OA\Put(
     *     path="/api/v1/children/{id}",
     *     summary="Update a specific child",
     *     tags={"Children"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID of the child to update",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             required={"first_name", "last_name", "birth_date", "gender"},
     *             @OA\Property(property="first_name", type="string", maxLength=255, example="John", description="First name of the child"),
     *             @OA\Property(property="last_name", type="string", maxLength=255, example="Doe", description="Last name of the child"),
     *             @OA\Property(property="birth_date", type="string", format="date", example="2015-06-15", description="Birth date of the child in YYYY-MM-DD format"),
     *             @OA\Property(
     *                 property="gender",
     *                 type="string",
     *                 enum={"male", "female", "other"},
     *                 example="male",
     *                 description="Gender of the child (male, female, or other)"
     *             ),
     *             @OA\Property(
     *                 property="avatar",
     *                 type="string",
     *                 format="binary",
     *                 description="Optional avatar image for the child, max size 10MB"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Child updated successfully",
     *         @OA\JsonContent(ref="#/components/schemas/ChildResource")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="The given data was invalid."),
     *             @OA\Property(property="errors", type="object", description="An object containing validation errors")
     *         )
     *     )
     * )
     */
    public function update(Request $request, Child $child)
    {
        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name'  => ['required', 'string', 'max:255'],
            'birth_date' => ['required', 'date', 'before:today'],
            'gender'     => ['required', 'string', new Enum(GenderEnum::class)],
            'avatar'     => ['sometimes', 'image', 'max:10240'],
        ]);

        if ($request->hasFile('avatar')) {
            $child->addMedia($request->file('avatar'))->toMediaCollection('avatars');
        }

        $child->update($validated);

        return response()->json(new ChildResource($child));
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/children/{id}",
     *     summary="Delete a specific child",
     *     tags={"Children"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID of the child to delete",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=204,
     *         description="Child deleted successfully"
     *     )
     * )
     */
    public function destroy(Child $child)
    {
        $child->delete();

        return response()->noContent();
    }
}
