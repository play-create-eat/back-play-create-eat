<?php

namespace App\Services;

use App\Exceptions\InvalidPassActivationDateException;
use Bavix\Wallet\Services\AtomicServiceInterface;
use chillerlan\QRCode\Output\QROutputInterface;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
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
use Illuminate\Support\Facades\Storage;
use Barryvdh\DomPDF\Facade\Pdf;
use Intervention\Image\Laravel\Facades\Image;
use Symfony\Component\HttpKernel\Exception\HttpException;

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

            $expiresFrom = $activationDate ? Carbon::instance($activationDate) : Carbon::now();
            $expiresAt = $duration->totalSeconds <= CarbonInterval::day()->totalSeconds
                ? $expiresFrom->endOfDay()
                : $expiresFrom->addYear();

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

    public function generateQRCode(string $serial, bool $force = false): void
    {
        $disk = Storage::disk('local');
        $qrImagePath = "passes/{$serial}/qr.png";
        $braceletPdfPath = "passes/{$serial}/bracelet.pdf";

        if (!$force && $disk->exists($braceletPdfPath)) {
            return;
        }

        # Calculate QR code size in pixels (22mm at 300 DPI)
        # 22mm * 300DPI / 25.4mm = 260 pixels
        $size_pixels = round(22 * 300 / 25.4);

        $options = new QROptions();
        $options->quietzoneSize = 4;
        $options->scale = 10;
        $options->returnResource = true;
        $options->outputType = QROutputInterface::IMAGICK;
        $options->quality = 90;

        $render = (new QRCode($options))->render($serial);
        $render->scaleImage($size_pixels, $size_pixels, true);
        $render->setImageFormat('png32');

        $imageContent = $render->getImageBlob();

        $qr = Image::read($imageContent)
            ->encodeByMediaType(\Intervention\Image\MediaType::IMAGE_PNG)
            ->toDataUri();

        $logoLong = Image::read(storage_path('app/public/logos/logo-long-pass.png'))
            ->rotate(-90)
            ->encodeByMediaType(\Intervention\Image\MediaType::IMAGE_PNG)
            ->toDataUri();

        $logo = Image::read(storage_path('app/public/logos/logo-pass.png'))
            ->rotate(-90)
            ->encodeByMediaType(\Intervention\Image\MediaType::IMAGE_PNG)
            ->toDataUri();

        $pdf = Pdf::loadView('pdf.pass-bracelet', [
            'qr' => $qr,
            'logoLong' => $logoLong,
            'logo' => $logo,
        ])->setPaper([0, 0, $this->mm2pt(25.4), $this->mm2pt(254)], 'portrait');

        $disk->put($qrImagePath, $imageContent);
        $pdf->save($braceletPdfPath, 'local');
    }

    public function getBraceletPdfPath(string $serial, bool $force = false): string
    {
        $disk = Storage::disk('local');
        $filePath = "passes/{$serial}/bracelet.pdf";
        $this->generateQRCode($serial, $force);

        abort_unless($disk->exists($filePath), 404);

        return $disk->path($filePath);
    }

    public function getQRImagePath(string $serial, bool $force = false): string
    {
        $disk = Storage::disk('local');
        $filePath = "passes/{$serial}/qr.png";
        $this->generateQRCode($serial, $force);

        abort_unless($disk->exists($filePath), 404);

        return $disk->path($filePath);
    }

    /**
     * @param Pass $pass
     * @param bool $confirmed
     * @return bool
     * @throws \Bavix\Wallet\Internal\Exceptions\ExceptionInterface
     * @throws \Throwable
     */
    public function refund(Pass $pass, bool $confirmed = true): bool
    {
        $pass->loadMissing(['user.family', 'transfer.deposit.payable']);

        $this->isRefundable($pass);

        $family = $pass->user->family;
        $deposit = $pass->transfer->deposit;

        return DB::transaction(function () use ($confirmed, $deposit, $family, $pass) {
            /** @var Product $product */
            $product = $deposit->payable;

            $result = $family->refund($product);
            $meta = [
                'product_id' => $product->id,
                'product_name' => $product->name,
                'pass_id' => $pass->id,
            ];

            app(AtomicServiceInterface::class)
                ->block($family->loyalty_wallet, function () use ($confirmed, $deposit, $family, $meta) {
                    $loyaltyPoints = (int)($deposit->meta['loyalty_points_used'] ?? 0);
                    $cashbackAmount = (int)($deposit->meta['cashback_amount'] ?? 0);

                    if ($loyaltyPoints > 0) {
                        $family->loyalty_wallet->deposit($loyaltyPoints,
                            meta: array_merge($meta, [
                                'description' => "Loyalty points returned due to refund.",
                            ]),
                            confirmed: $confirmed,
                        );
                    }

                    if ($cashbackAmount > 0) {
                        $family->loyalty_wallet->forceWithdraw($cashbackAmount,
                            meta: array_merge($meta, [
                                'description' => "Cashback reversed due to refund",
                            ]),
                            confirmed: $confirmed,
                        );
                    }
                });

            $pass->delete();

            return $result;
        });
    }

    /**
     * @param Pass $pass
     * @return bool
     * @throws \Throwable
     */
    public function isRefundable(Pass $pass): bool
    {
        $pass->loadMissing(['transfer']);

        throw_unless(
            condition: $pass->isUnused(),
            exception: new HttpException(409, 'Refund unavailable: this ticket has already been used.'),
        );

        throw_if(
            condition: $pass->isExpired(),
            exception: new HttpException(409, 'Refund unavailable: this ticket has expired.')
        );

        throw_unless(
            condition: $pass->transfer->status === Transfer::STATUS_PAID,
            exception: new HttpException(409, 'Refund unavailable: this ticket is not refundable.'),
        );

        return true;
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
        User    $user,
        Product $product,
        Carbon  $date = null,
        int     $loyaltyPointAmount = 0,
        array   $meta = []
    ): Transfer
    {
        $family = $user->loadMissing('family')->family;
        $productPrice = $product->getFinalPrice($date);
        $loyaltyWallet = $family->loyalty_wallet;


        if ($loyaltyPointAmount > 0) {
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

        $cashbackAmount = 0;

        if ($productPrice > 0 && $product->cashback_percent > 0) {
            $cashbackAmount = round($productPrice * $product->cashback_percent / 100);
        }

        $cart = app(Cart::class)
            ->withItem($product, pricePerItem: $productPrice)
            ->withMeta([
                ...$meta,
                'loyalty_points_used'       => $loyaltyPointAmount,
                'discount_price_weekday'    => $product->discount_price_weekday,
                'discount_price_weekend'    => $product->discount_price_weekend,
                'discount_percent'          => $product->discount_percent,
                'cashback_percent'          => $product->cashback_percent,
                'cashback_amount'           => $cashbackAmount,
                'fee_percent'               => $product->fee_percent,
            ]);

        list($transfer) = array_values($family->payCart($cart));

        if ($cashbackAmount > 0) {

            $loyaltyWallet->deposit(
                $cashbackAmount,
                [
                    'name' => 'Cashback for product "' . $product->id . '" purchase ',
                    'product_id' => $product->id,
                    'transfer_id' => $transfer->id,
                ]
            );
        }

        return $transfer;
    }

    private function mm2pt(float $mm): float
    {
        return $mm * 72 / 25.4;
    }
}
