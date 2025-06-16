<?php

namespace App\Http\Controllers\Api\v1;

use App\Data\Products\PassPurchaseData;
use App\Data\Products\PassPurchaseProductData;
use App\Enums\ProductTypeEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\v1\ProductPurchaseRequest;
use App\Http\Requests\Api\v1\ProductRefundRequest;
use App\Http\Resources\Api\v1\FamilyPassResource;
use App\Http\Resources\Api\v1\ProductResource;
use App\Models\Product;
use App\Services\PassService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

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
    public function index(Request $request)
    {
        $filters = $request->validate([
            'limit' => ['integer', 'min:1', 'max:50'],
            'duration' => ['array', 'min:1', Rule::in(array_keys(config('passes.durations')))],
            'date' => ['date', 'after_or_equal:today'],
            'feature' => ['array', 'min:1'],
            'feature.*' => ['integer'],
            'type' => [new Enum(ProductTypeEnum::class)],
        ]);

        $limit = $filters['limit'] ?? 10;
        $type = $filters['type'] ?? ProductTypeEnum::BASIC;

        $products = Product::available()
            ->with(['features'])
            ->where('type', $type)
            ->when($request->filled('duration'), function ($query) use ($request) {
                $query->whereIn('duration_time', $request->input('duration'));
            })
            ->when($request->filled('feature'), function ($query) use ($request) {
                $query->whereHas('features', function ($query) use ($request) {
                    $query->whereIn('id', $request->input('feature'));
                });
            })
            ->limit($limit)
            ->get();

        return ProductResource::collection($products);
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
     *    *        @OA\Property(property="product_id", type="integer", format="int64", description="The ID of the product", minimum=0, example=456),
     *             @OA\Property(property="date", type="string", format="date", description="The activation date for the product in YYYY-MM-DD format", example="2025-03-24"),
     *             @OA\Property(property="loyalty_points_amount", type="integer", format="int64", description="Loyalty point used for discount", minimum=0, example=100),
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
        $data = PassPurchaseData::from([
            'loyaltyPointsAmount' => (int)$request->input('loyalty_points_amount'),
            'products' => PassPurchaseProductData::collect($request->input('products')),
        ]);

        $passes = app(PassService::class)->purchaseMultiple(
            user: $user,
            data: $data,
        );

        return FamilyPassResource::collection($passes);
    }

    /**
     * @OA\Post(
     *     path="/api/v1/product/refund",
     *     summary="Refund a ticket",
     *     tags={"Product"},
     *     description="Refund an valid pass.",
     *     security={{"sanctum": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"pass_id"},
     *             @OA\Property(property="pass_id", type="integer", format="int64", description="The ID of the pass", minimum=1, example=1)
     *         )
     *     ),
     *     @OA\Response(
     *         response=204,
     *         description="Refund successfully processed"
     *     ),
     *     @OA\Response(
     *         response=409,
     *         description="Ticket has already been used.",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Refund unavailable: this ticket has already been used.")
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
    public function refund(ProductRefundRequest $request)
    {
        $user = auth()->guard('sanctum')->user()->load('family');
        $pass = $user->family->passes()->findOrFail($request->input('pass_id'));

        app(PassService::class)->refund($pass);

        return response()->noContent();
    }
}
