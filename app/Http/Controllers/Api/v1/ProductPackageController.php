<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\v1\ProductPackagePurchaseRequest;
use App\Http\Requests\Api\v1\PassPackageRedeemRequest;
use App\Http\Requests\Api\v1\PassPackageRefundRequest;
use App\Http\Resources\Api\v1\FamilyPassPackageResource;
use App\Http\Resources\Api\v1\FamilyPassResource;
use App\Http\Resources\Api\v1\ProductPackageResource;
use App\Models\Child;
use App\Models\ProductPackage;
use App\Services\ProductPackageService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ProductPackageController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'limit' => ['integer', 'min:1', 'max:50'],
            'date' => ['date', 'after_or_equal:today'],
            'feature' => ['array', 'min:1'],
            'feature.*' => ['integer'],
        ]);

        $limit = $filters['limit'] ?? 10;
        $date = $filters['date'] ?? today();

        $products = ProductPackage::publicAvailable()
            ->with(['product.features'])
            ->where('campaign_active', false)
            ->orWhere(function ($query) use ($date) {
                $query->activeCampaign($date);
            })
            ->when($request->filled('feature'), function ($query) use ($request) {
                $query->whereHas('product.features', function ($query) use ($request) {
                    $query->whereIn('id', $request->input('feature'));
                });
            })
            ->limit($limit)
            ->get();

        return ProductPackageResource::collection($products);
    }

    public function purchase(ProductPackagePurchaseRequest $request)
    {
        $user = auth()->guard('sanctum')->user()->load('family');

        $loyaltyPointsAmount = (int)$request->input('loyalty_points_amount');
        $child = Child::findOrFail($request->input('child_id'));
        $productPackage = ProductPackage::available()->findOrFail($request->input('product_package_id'));

        $passPackage = app(ProductPackageService::class)->purchase(
            user: $user,
            child: $child,
            productPackage: $productPackage,
        );

        $passPackage->loadMissing(['productPackage.product.features']);

        return new FamilyPassPackageResource($passPackage);
    }

    public function redeem(PassPackageRedeemRequest $request)
    {
        $user = auth()->guard('sanctum')->user()->load('family');
        $passPackage = $user->family
            ->passPackages()
            ->findOrFail($request->input('pass_package_id'));

        $pass = app(ProductPackageService::class)->redeem(
            passPackage: $passPackage,
            activationDate: Carbon::parse($request->input('date')),
        );

        return new FamilyPassResource($pass);
    }

    public function refund(PassPackageRefundRequest $request)
    {
        $user = auth()->guard('sanctum')->user()->load('family');

        $passPackage = $user->family
            ->passPackages()
            ->findOrFail($request->input('pass_package_id'));

        app(ProductPackageService::class)->refund($passPackage);

        return response()->noContent();
    }
}
