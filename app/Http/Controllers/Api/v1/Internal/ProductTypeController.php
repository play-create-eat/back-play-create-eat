<?php

namespace App\Http\Controllers\Api\v1\Internal;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\v1\Internal\ProductTypeResource;
use App\Models\ProductType;

/**
 * @OA\Schema(
 *     schema="ProductTypeResource",
 *     type="object",
 *     title="Product Type Resource",
 *     description="Internal Product Type resource object",
 *     @OA\Property(property="id", type="integer", example=1, description="Product Type ID"),
 *     @OA\Property(property="name", type="string", example="Playground", description="Product Type name"),
 *     @OA\Property(property="description", type="string", description="Product Type description"),
 * )
 */
class ProductTypeController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/v1/internal/product-type",
     *     summary="Get all available product types",
     *     tags={"InternalProductType"},
     *     security={{"sanctum": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="List of product types",
     *         @OA\JsonContent(type="array", @OA\Items(ref="#/components/schemas/ProductTypeResource"))
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
        $productTypes = ProductType::orderBy('name', 'ASC')->get();
        return ProductTypeResource::collection($productTypes);
    }
}
