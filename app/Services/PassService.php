<?php

namespace App\Services;

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

    public function purchase(User $user, Child $child, Product $product, bool $isFree = false): Pass
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

        return DB::transaction(function () use ($user, $child, $product, $isFree) {
            if ($isFree) {
                $transfer = $user->family->payFree($product);
            } else {
                $transfer = $user->family->pay($product);
            }

            $duration = CarbonInterval::minutes($product->duration_time);
            $expiresAt = $duration->totalSeconds <= CarbonInterval::day()->totalSeconds
                ? Carbon::now()->endOfDay()
                : Carbon::now()->addYear();

            $pass = new Pass();
            $pass->serial = static::generateSerial();
            $pass->remaining_time = round($duration->totalMinutes);
            $pass->is_extendable = $product->is_extendable;
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
}
