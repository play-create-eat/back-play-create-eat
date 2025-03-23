<?php

namespace App\Services;

use App\Exceptions\ChildrenFamilyNotAssociatedException;
use App\Models\Child;
use App\Models\Pass;
use App\Models\Product;
use App\Models\User;
use App\Exceptions\InvalidExtendableTimeException;
use App\Exceptions\PassNotExtendableException;
use App\Exceptions\PassExpiredException;
use App\Exceptions\PassRemainingTimeExceededException;
use App\Exceptions\ProductNotAvailableException;
use Carbon\Carbon;
use Carbon\CarbonInterval;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PassService
{
    public static function generateSerial(): string
    {
        return 'SN-' . Str::orderedUuid();
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
            $children->family->is($user->family),
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

    public function scan(string $serial, string $productTypeId): Pass
    {
        $pass = static::findPassBySerial($serial);

        throw_if($pass->isExpired(), new PassExpiredException($pass));

        $now = Carbon::now();

        // Scan for exit
        if ($pass->entered_at) {
            $timeLapsed = $pass->started_at->diffInMinutes($now);

            $pass->entered_at = null;
            $pass->exited_at = $now;
            $pass->remaining_time -= $timeLapsed;

            if ($pass->remaining_time <= 0) {
                $pass->expires_at = $now;
            }

            $pass->save();
            return $pass;
        }

        // Scan for enter
        throw_if($pass->remaining_time <= 0, new PassRemainingTimeExceededException($pass));

        $pass->entered_at = $now;
        $pass->exited_at = null;
        $pass->save();

        return $pass;
    }

    public function extend(string $serial, int $minutes): Pass
    {
        $pass = static::findPassBySerial($serial);

        throw_unless($pass->is_extendable, new PassNotExtendableException($pass));
        throw_unless($minutes > 0, new InvalidExtendableTimeException($minutes));

        // @TODO How the payment should be charged for this ?
        // @TODO Check if need to pay for extra remaining time

        $pass->remaining_time += $minutes;
        $pass->save();

        return $pass;
    }
}
