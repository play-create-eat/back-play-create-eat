<?php

namespace App\Http\Controllers\Api\v1;

use App\Enums\FamilyPassPackageStatusEnum;
use App\Enums\FamilyPassStatusEnum;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\v1\FamilyPassPackageResource;
use App\Http\Resources\Api\v1\FamilyPassResource;
use App\Http\Resources\Api\v1\UserResource;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Enum;

class FamilyController extends Controller
{
    public function members()
    {
        $family = auth()->guard('sanctum')->user()->family;

        $members = User::with(['family', 'profile', 'roles', 'roles.permissions'])
            ->where('family_id', $family->id)->get();

        return UserResource::collection($members);
    }

    public function passes(Request $request)
    {
        $request->validate([
            'limit' => ['integer', 'min:1', 'max:50'],
            'status' => [new Enum(FamilyPassStatusEnum::class)],
            'children' => ['array', 'min:1'],
            'children.*' => ['integer'],
        ]);

        $family = auth()->guard('sanctum')->user()->family;
        $passes = $family->passes()
            ->with(['children', 'transfer.deposit'])
            ->when($request->filled('status'), function (Builder $query) use ($request) {
                $now = Carbon::today();
                $status = FamilyPassStatusEnum::tryFrom($request->input('status'));

                return match ($status) {
                    FamilyPassStatusEnum::Active => $query->whereDate('activation_date', '=', $now),
                    FamilyPassStatusEnum::Feature => $query->whereDate('activation_date', '>', $now),
                    FamilyPassStatusEnum::Expired => $query->whereDate('activation_date', '<', $now),
                    default => $query,
                };
            })
            ->when($request->filled('children'), function ($query) use ($request) {
                $query->whereIn('child_id', $request->input('children'));
            })
            ->paginate($request->input('limit') ?? 20);

        return FamilyPassResource::collection($passes);
    }

    public function passPackages(Request $request)
    {
        $request->validate([
            'limit' => ['integer', 'min:1', 'max:50'],
            'status' => [new Enum(FamilyPassPackageStatusEnum::class)],
            'children' => ['array', 'min:1'],
            'children.*' => ['integer'],
        ]);

        $family = auth()->guard('sanctum')->user()->family;
        $passes = $family->passPackages()
            ->with(['children', 'productPackage', 'transfer.deposit'])
            ->when($request->filled('status'), function (Builder $query) use ($request) {
                $status = FamilyPassPackageStatusEnum::tryFrom($request->input('status'));

                return match ($status) {
                    FamilyPassPackageStatusEnum::Active => $query->where('quantity', '>', 0),
                    FamilyPassPackageStatusEnum::Expired => $query->where('quantity', 0),
                    default => $query,
                };
            })
            ->when($request->filled('children'), function ($query) use ($request) {
                $query->whereIn('child_id', $request->input('children'));
            })
            ->paginate($request->input('limit') ?? 20);

        return FamilyPassPackageResource::collection($passes);
    }
}
