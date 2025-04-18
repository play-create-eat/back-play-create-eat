<?php

namespace App\Services;

use App\Exceptions\InvalidPassActivationDateException;
use DateTime;
use App\Exceptions\InsufficientCashbackBalanceException;
use App\Models\Child;
use App\Models\Pass;
use App\Models\Product;
use App\Models\ProductType;
use App\Models\User;
use App\Exceptions\ChildrenFamilyNotAssociatedException;
use App\Exceptions\PassFeatureNotAvailableException;
use App\Exceptions\InvalidExtendableTimeException;
use App\Exceptions\PassNotExtendableException;
use App\Exceptions\PassExpiredException;
use App\Exceptions\ProductNotAvailableException;
use App\Notifications\PassCheckInNotification;
use App\Notifications\PassCheckOutNotification;
use Bavix\Wallet\Models\Transfer;
use Bavix\Wallet\Objects\Cart;
use Carbon\Carbon;
use Carbon\CarbonInterval;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PassService
{
    public static function generateSerial(): string
    {
        return Str::upper('SN-' . Str::orderedUuid());
    }

    public static function findPassBySerial(string $serial): Pass
    {
        return Pass::where('serial', $serial)->firstOrFail();
    }

    public function purchase(
        User     $user,
        Child    $child,
        Product  $product,
        bool     $isFree = false,
        int      $loyaltyPointAmount = 0,
        DateTime $activationDate = null,
        array    $meta = []
    ): Pass
    {
        $user->loadMissing('family');
        $child->loadMissing('family');

        throw_unless($product->is_available, new ProductNotAvailableException($product));

        throw_unless(
            $child->family->is($user->family),
            new ChildrenFamilyNotAssociatedException(
                child: $child,
                currentFamily: $user->family,
            )
        );

        return DB::transaction(function () use ($user, $child, $product, $activationDate, $isFree, $loyaltyPointAmount, $meta) {
            if ($isFree) {
                $transfer = $user->family->payFree($product);
            } else {
                $transfer = $this->payWithLoyaltyPoints(
                    user: $user,
                    product: $product,
                    date: $activationDate,
                    loyaltyPointAmount: $loyaltyPointAmount,
                    meta: $meta
                );
            }

            $duration = CarbonInterval::minutes($product->duration_time);
            $expiresAt = $duration->totalSeconds <= CarbonInterval::day()->totalSeconds
                ? Carbon::now()->endOfDay()
                : Carbon::now()->addYear();

            $pass = new Pass();
            $pass->serial = static::generateSerial();
            $pass->remaining_time = round($duration->totalMinutes);
            $pass->is_extendable = $product->is_extendable;
            $pass->activation_date = $activationDate ?: today();
            $pass->expires_at = $expiresAt;
            $pass->children()->associate($child);
            $pass->user()->associate($user);
            $pass->transfer()->associate($transfer);
            $pass->save();

            return $pass;
        });
    }

    /**
     * @param string $serial
     * @param int $productTypeId
     * @return Pass
     * @throws \Throwable
     * @throws PassExpiredException
     */
    public function scan(string $serial, int $productTypeId): Pass
    {
        $pass = static::findPassBySerial($serial);
        $isCheckIn = !$pass->entered_at || $pass->entered_at && $pass->exited_at;

        if ($isCheckIn) {
            $pass = $this->markPassCheckIn($pass, $productTypeId);
        } else {
            $pass = $this->markPassCheckOut($pass);
        }

        return $pass->fresh();
    }

    /**
     * @TODO Work in progress
     *
     * @param string $serial
     * @param int $minutes
     * @return Pass
     * @throws \Throwable
     * @throws PassNotExtendableException
     * @throws InvalidExtendableTimeException
     */
    public function extend(string $serial, int $minutes): Pass
    {
        $pass = static::findPassBySerial($serial);

        throw_unless(
            condition: $pass->is_extendable,
            exception: new PassNotExtendableException($pass)
        );

        throw_unless(
            condition: $minutes > 0,
            exception: new InvalidExtendableTimeException($minutes)
        );

        // @TODO How the payment should be charged for this ?
        // @TODO Check if need to pay for extra remaining time

        $pass->remaining_time += $minutes;
        $pass->save();

        return $pass;
    }

    public function isPassFeatureAvailable(Pass $pass, ProductType $productType): bool
    {
        $pass->loadMissing("transfer.deposit");
        $features = collect($pass->transfer->deposit->meta["features"] ?? []);

        return $features->has($productType->id);
    }

    /**
     * @param Pass $pass
     * @param int $productTypeId
     * @return Pass
     * @throws \Throwable
     * @throws PassFeatureNotAvailableException
     * @throws PassExpiredException
     */
    protected function markPassCheckIn(Pass $pass, int $productTypeId): Pass
    {
        $pass->loadMissing('user');

        throw_if(
            condition: $pass->isExpired(),
            exception: new PassExpiredException($pass)
        );

        throw_unless(
            condition: $pass->activation_date->isToday(),
            exception: new InvalidPassActivationDateException($pass)
        );

        $productType = ProductType::findOrFail($productTypeId);

        throw_unless(
            condition: $this->isPassFeatureAvailable($pass, $productType),
            exception: new PassFeatureNotAvailableException($pass, $productType)
        );

        $pass->fill([
            'entered_at' => Carbon::now(),
            'exited_at' => null,
        ]);

        $pass->save();
        $pass->user->notify(new PassCheckInNotification($pass));

        return $pass;
    }

    /**
     * @param Pass $pass
     * @return Pass
     */
    protected function markPassCheckOut(Pass $pass): Pass
    {
        $pass->loadMissing('user');
        $now = Carbon::now();
        $timeLapsed = round($pass->entered_at->diffInMinutes($now));

        $pass->fill([
            'exited_at' => $now,
            'remaining_time' => $pass->remaining_time - $timeLapsed,
        ]);

        if ($pass->remaining_time <= 0) {
            $pass->expires_at = $now;
        }

        $pass->save();
        $pass->user->notify(new PassCheckOutNotification($pass));

        return $pass;
    }

    /**
     * @throws \Bavix\Wallet\Internal\Exceptions\ExceptionInterface
     * @throws \Throwable
     */
    protected function payWithLoyaltyPoints(
        User $user,
        Product $product,
        Carbon $date = null,
        int $loyaltyPointAmount = 0,
        array $meta = []
    ): Transfer
    {
        $family = $user->loadMissing('family')->family;
        $productPrice = $product->getFinalPrice($date);

        if ($loyaltyPointAmount > 0) {
            $loyaltyWallet = $family->loyalty_wallet;

            if ($loyaltyPointAmount > $productPrice) {
                $loyaltyPointAmount = $productPrice;
            }

            throw_if($loyaltyWallet->balance < $loyaltyPointAmount, new InsufficientCashbackBalanceException(
                amount: $loyaltyPointAmount,
                balance: $loyaltyWallet->balance,
            ));

            $loyaltyWallet->withdraw(
                amount: $loyaltyPointAmount,
                meta: [
                    ...$meta,
                    'description' => 'Loyalty points redeemed successfully for product discount.',
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'product_price' => $productPrice,
                ],
            );

            $productPrice = max(0, $productPrice - $loyaltyPointAmount);
        }

        $cart = app(Cart::class)
            ->withItem($product, pricePerItem: $productPrice)
            ->withMeta([
                ...$meta,
                'loyalty_points_used' => $loyaltyPointAmount,
                'discount_percent' => $product->discount_percent,
                'fee_percent' => $product->fee_percent,
            ]);

        list($transfer) = array_values($family->payCart($cart));

        return $transfer;
    }
}
