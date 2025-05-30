<?php

namespace App\Services;

use App\Exceptions\ChildrenFamilyNotAssociatedException;
use App\Exceptions\InsufficientBalanceException;
use App\Exceptions\PassAlreadyExistsException;
use App\Exceptions\ProductPackageNotAvailableException;
use App\Models\Child;
use App\Models\Pass;
use App\Models\PassPackage;
use App\Models\ProductPackage;
use App\Models\User;
use Bavix\Wallet\Models\Transfer;
use Bavix\Wallet\Objects\Cart;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use DateTime;
use Illuminate\Support\Facades\DB;

class ProductPackageService
{
    public function purchase(
        User           $user,
        Child          $child,
        ProductPackage $productPackage,
        bool           $isFree = false,
        array          $meta = []
    ): PassPackage
    {
        $user->loadMissing('family');
        $child->loadMissing('family');

        throw_unless($productPackage->is_available, new ProductPackageNotAvailableException($productPackage));

        throw_unless(
            $child->family->is($user->family),
            new ChildrenFamilyNotAssociatedException(
                child: $child,
                currentFamily: $user->family,
            )
        );

        return DB::transaction(function () use ($user, $child, $productPackage, $isFree, $meta) {
            if ($isFree) {
                $transfer = $user->family->payFree($productPackage);
            } else {
                $transfer = $this->payWithLoyaltyPoints(
                    user: $user,
                    productPackage: $productPackage,
                    meta: $meta
                );
            }

            $passPackage = new PassPackage();
            $passPackage->quantity = $productPackage->product_quantity;
            $passPackage->productPackage()->associate($productPackage);
            $passPackage->children()->associate($child);
            $passPackage->user()->associate($user);
            $passPackage->transfer()->associate($transfer);
            $passPackage->save();

            return $passPackage;
        });
    }

    /**
     * @param PassPackage $passPackage
     * @param DateTime|null $activationDate
     * @return Pass
     * @throws \Throwable
     */
    public function redeem(
        PassPackage     $passPackage,
        DateTime        $activationDate = null,
        array           $meta = []
    ): Pass
    {
        throw_unless($passPackage->quantity > 0,
            new InsufficientBalanceException(
                amount: 1,
                balance: $passPackage->quantity,
            )
        );

        $activationDate = $activationDate ? Carbon::instance($activationDate) : Carbon::now();

        throw_if($this->isPassActivatedOn($passPackage, $activationDate), new PassAlreadyExistsException());

        $passPackage->loadMissing(['children', 'productPackage.product', 'user']);

        return DB::transaction(function () use ($passPackage, $activationDate, $meta) {
            $pass = app(PassService::class)->purchase(
                user: $passPackage->user,
                child: $passPackage->children,
                product: $passPackage->productPackage->product,
                isFree: true,
                activationDate: $activationDate,
                meta: [
                    ...$meta,
                    'pass_package_id' => $passPackage->id,
                    'product_package_id' => $passPackage->productPackage->id,
                    'quantity_balance' => $passPackage->quantity,
                ],
            );

            $pass->passPackage()->associate($passPackage);
            $pass->save();

            $passPackage->quantity = $passPackage->quantity - 1;
            $passPackage->save();

            return $pass;
        });
    }

    public function isPassActivatedOn(PassPackage $passPackage, CarbonInterface $date): bool
    {
        return $passPackage->passes()->where('activation_date', $date)->exists();
    }

    /**
     * @throws \Bavix\Wallet\Internal\Exceptions\ExceptionInterface
     * @throws \Throwable
     */
    protected function payWithLoyaltyPoints(
        User           $user,
        ProductPackage $productPackage,
        array          $meta = []
    ): Transfer
    {
        $family = $user->loadMissing('family')->family;
        $productPrice = $productPackage->getFinalPrice();
        $loyaltyWallet = $family->loyalty_wallet;

        $cart = app(Cart::class)
            ->withItem($productPackage, pricePerItem: $productPrice)
            ->withMeta($meta);

        list($transfer) = array_values($family->payCart($cart));

        $cashbackAmount = $productPackage->cashback_amount;
        if ($productPrice > 0 && $cashbackAmount > 0) {
            $loyaltyWallet->deposit(
                $cashbackAmount,
                [
                    'name' => 'Cashback for product package "' . $productPackage->id . '" purchase ',
                    'product_package_id' => $productPackage->id,
                    'transfer_id' => $transfer->id,
                ]
            );
        }

        return $transfer;
    }
}
