<?php

namespace App\Services;

use App\Data\Products\PassPurchaseData;
use App\Data\Products\PassPurchaseProductData;
use App\Exceptions\ChildrenFamilyNotAssociatedException;
use App\Exceptions\InsufficientBalanceException;
use App\Exceptions\InsufficientCashbackBalanceException;
use App\Exceptions\PassAlreadyExistsException;
use App\Exceptions\ProductPackageNotAvailableException;
use App\Models\Child;
use App\Models\Pass;
use App\Models\PassPackage;
use App\Models\ProductPackage;
use App\Models\User;
use Bavix\Wallet\Models\Transfer;
use Bavix\Wallet\Objects\Cart;
use Bavix\Wallet\Services\AtomicServiceInterface;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use DateTime;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;

class ProductPackageService
{
    public function purchase(
        User           $user,
        Child          $child,
        ProductPackage $productPackage,
        int            $loyaltyPointsAmount = 0,
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

        return DB::transaction(function () use ($user, $child, $productPackage, $isFree, $meta, $loyaltyPointsAmount) {
            if ($isFree) {
                $transfer = $user->family->payFree($productPackage);
            } else {
                $transfer = $this->payWithLoyaltyPoints(
                    user: $user,
                    productPackage: $productPackage,
                    loyaltyPointsAmount: $loyaltyPointsAmount,
                    meta: $meta,
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
        PassPackage        $passPackage,
        \DateTimeInterface $activationDate = null,
        array              $meta = []
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
            $products = [
                [
                    'product_id' => $passPackage->productPackage->product->id,
                    'child_id' => $passPackage->children->id,
                    'date' => $activationDate,
                ],
            ];

            list($pass) = app(PassService::class)->purchaseMultiple(
                user: $passPackage->user,
                data: PassPurchaseData::from([
                    'gift' => true,
                    'products' => PassPurchaseProductData::collect($products),
                ]),
                meta: [
                    'pass_package_id' => $passPackage->id,
                    'product_package_id' => $passPackage->productPackage->id,
                    'quantity_balance' => $passPackage->quantity,
                ],
            );

            $pass->passPackage()->associate($passPackage);
            $pass->save();

            $passPackage->quantity = $passPackage->quantity - 1;
            $passPackage->save();

            return $pass->loadMissing(['passPackage.productPackage']);
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
        int            $loyaltyPointsAmount = 0,
        array          $meta = []
    ): Transfer
    {
        $family = $user->loadMissing('family')->family;
        $loyaltyWallet = $family->loyalty_wallet;

        $productPrice = $productPackage->getFinalPrice();
        $discountAmount = max(0, $loyaltyPointsAmount);

        if ($discountAmount > 0) {
            throw_if($loyaltyWallet->balance < $discountAmount, new InsufficientCashbackBalanceException(
                amount: $discountAmount,
                balance: $loyaltyWallet->balance,
            ));

            $productPrice = max(0, $productPrice - $discountAmount);
        }

        $cart = app(Cart::class)
            ->withItem($productPackage, pricePerItem: $productPrice)
            ->withMeta([
                ...$meta,
                'discount' => $discountAmount,
                'price_base' => $productPackage->getFinalPrice(),
                'price_final' => $productPrice,
            ]);

        list($transfer) = array_values($family->payCart($cart));

        if ($discountAmount) {
            $loyaltyWallet->withdraw(
                amount: $discountAmount,
                meta: [
                    ...$meta,
                    'description' => 'Loyalty points redeemed for pass package purchase.',
                    'cart' => $cart->getMeta(),
                ],
            );
        }

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

    public function refund(
        PassPackage $passPackage,
        bool        $confirmed = true,
        string      $refundComment = null,
        bool        $allowUsedTickets = false
    )
    {
        $passPackage->loadMissing(['user.family', 'productPackage', 'transfer.deposit.payable']);

        $this->isRefundable($passPackage, $allowUsedTickets);

        $family = $passPackage->user->family;
        $deposit = $passPackage->transfer->deposit;

        return DB::transaction(function () use ($confirmed, $deposit, $family, $passPackage, $refundComment) {
            /** @var ProductPackage $productPackage */
            $productPackage = $deposit->payable;
            $refundAmount = $deposit->amount;

            $meta = [
                'product_package_id' => $productPackage->id,
                'product_package_name' => $productPackage->name,
                'pass_package_id' => $passPackage->id,
                'description' => 'Refund for package "' . $productPackage->name . '"',
                'transfer_uuid' => $passPackage->transfer->uuid,
                'transfer_meta' => $deposit->meta ?? [],
                'refund_date' => now()->toISOString(),
            ];

            if ($refundComment) {
                $meta['reason'] = $refundComment;
            }

            $family->main_wallet->deposit($refundAmount, $meta, $confirmed);

            $passPackage->transfer->update(['status' => 'refund']);

            $loyaltyPoints = (int)($deposit->meta['loyalty_points_used'] ?? 0);
            $cashbackAmount = (int)($deposit->meta['cashback_amount'] ?? 0);

            app(AtomicServiceInterface::class)
                ->block($family->loyalty_wallet, function () use ($confirmed, $deposit, $family, $loyaltyPoints, $cashbackAmount, $meta) {
                    if ($loyaltyPoints > 0) {
                        $family->loyalty_wallet->deposit($loyaltyPoints,
                            meta: array_merge($meta, [
                                'description' => "Loyalty points returned due to package refund.",
                            ]),
                            confirmed: $confirmed,
                        );
                    }

                    if ($cashbackAmount > 0) {
                        $family->loyalty_wallet->forceWithdraw($cashbackAmount,
                            meta: array_merge($meta, [
                                'description' => "Cashback reversed due to package refund",
                            ]),
                            confirmed: $confirmed,
                        );
                    }
                });

            $passPackage->delete();

            return true;
        });
    }

    /**
     * @param PassPackage $passPackage
     * @param bool $allowUsedTickets
     * @return bool
     * @throws Throwable
     */
    public function isRefundable(PassPackage $passPackage, bool $allowUsedTickets = false): bool
    {
        $passPackage->loadMissing(['productPackage', 'transfer']);

        if (!$allowUsedTickets) {
            throw_unless(
                condition: $passPackage->quantity === $passPackage->productPackage->product_quantity,
                exception: new HttpException(409, 'Refund unavailable: this package has already been used.'),
            );
        }

        throw_unless(
            condition: $passPackage->transfer->status === Transfer::STATUS_PAID,
            exception: new HttpException(409, 'Refund unavailable: this package is not refundable.'),
        );

        return true;
    }
}
