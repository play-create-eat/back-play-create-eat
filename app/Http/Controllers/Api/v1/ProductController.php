<?php

namespace App\Http\Controllers\Api\v1;

use App\Filament\Resources\ProductResource;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\v1\ProductPurchaseRequest;
use App\Http\Resources\Api\v1\FamilyPassResource;
use App\Models\Child;
use App\Models\Product;
use App\Services\PassService;
use Carbon\Carbon;
use Illuminate\Validation\Rule;

/**
 * @OA\Schema(
 *     schema="ProductResource",
 *     type="object",
 *     title="Product Resource",
 *     description="Product resource object",
 *     @OA\Property(property="id", type="integer", example=1, description="Product ID"),
 *     @OA\Property(property="name", type="string", example="Unlock daily access to playground", description="Product description"),
 *     @OA\Property(property="price", type="integer", example="10000", description="Product price in cents"),
 *     @OA\Property(property="price_weekend", type="integer", example="500", description="Product weekend price in cents"),
 *     @OA\Property(property="fee_percent", type="double", example="10.0", description="Product fee percent"),
 *     @OA\Property(property="is_extendable", type="boolean", example="true", description="Product can be extended")
 * )
 */
class ProductController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/v1/product",
     *     summary="Get all available products for customer",
     *     tags={"Product"},
     *     security={{"sanctum": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="List of products",
     *         @OA\JsonContent(type="array", @OA\Items(ref="#/components/schemas/ProductResource"))
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         @OA\JsonContent(@OA\Property(property="message", type="string", example="Unauthenticated."))
     *     )
     * )
     */
    public function index()
    {
        $products = Product::available()->with(['features'])->get();

        return response()->json(ProductResource::collection($products));
    }

    /**
     * @OA\Post(
     *     path="/api/v1/product/purchase",
     *     summary="Purchase a product",
     *     tags={"Product"},
     *     description="Purchase an product and pass for children.",
     *     security={{"sanctum": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"child_id", "product_id", "date"},
     *             @OA\Property(property="child_id", type="integer", format="int64", description="The ID of the child", minimum=1, example=1),
     *    *             @OA\Property(property="product_id", type="integer", format="int64", description="The ID of the product", minimum=0, example=456)
     *             @OA\Property(property="date", type="string", format="date", description="The activation date for the product in YYYY-MM-DD format", example="2025-03-24")
     *             @OA\Property(property="loyalty_points_amount", type="integer", format="int64", description="Loyalty point used for discount", minimum=0, example=100)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Pass successfully purchased",
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
    public function purchase(ProductPurchaseRequest $request)
    {
        $user = auth()->guard('sanctum')->user()->load('family');

        $request->validate([
            'child_id' => 'required|integer',
            'product_id' => 'required|integer',
            'date' => [
                'required',
                Rule::date()->afterOrEqual(today()),
            ],
            'loyalty_points_amount' => 'required|integer|min:0',
        ]);

        $child = Child::findOrFail($request->input('child_id'));
        $product = Product::available()->findOrFail($request->input('product_id'));
        $loyaltyPointAmount = (int)$request->input('loyalty_points_amount');

        $pass = app(PassService::class)->purchase(
            user: $user,
            child: $child,
            product: $product,
            loyaltyPointAmount: $loyaltyPointAmount,
            activationDate: Carbon::parse($request->input('date'))
        );

        return new FamilyPassResource($pass);
    }
}
