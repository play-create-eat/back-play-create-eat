<?php

namespace App\Services;

use App\Data\Products\PassInfoData;
use App\Data\Products\PassPurchaseData;
use App\Data\Products\PassPurchaseProductData;
use App\Exceptions\ChildrenFamilyNotAssociatedException;
use App\Exceptions\InsufficientCashbackBalanceException;
use App\Exceptions\InvalidExtendableTimeException;
use App\Exceptions\InvalidPassActivationDateException;
use App\Exceptions\PassExpiredException;
use App\Exceptions\PassFeatureNotAvailableException;
use App\Exceptions\PassNotExtendableException;
use App\Exceptions\ProductNotAvailableException;
use App\Models\Child;
use App\Models\Pass;
use App\Models\Product;
use App\Models\ProductType;
use App\Models\User;
use App\Notifications\PassCheckInNotification;
use App\Notifications\PassCheckOutNotification;
use Barryvdh\DomPDF\Facade\Pdf;
use Bavix\Wallet\Internal\Exceptions\ExceptionInterface;
use Bavix\Wallet\Models\Transfer;
use Bavix\Wallet\Objects\Cart;
use Bavix\Wallet\Services\AtomicServiceInterface;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterval;
use chillerlan\QRCode\Output\QROutputInterface;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use DateTime;
use Illuminate\Support\Enumerable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Laravel\Facades\Image;
use Intervention\Image\MediaType;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;

class PassService
{
    /**
     * @param User $user
     * @param PassPurchaseData $data
     * @param array $meta
     * @return array<int, Pass>
     */
    public function purchaseMultiple(
        User             $user,
        PassPurchaseData $data,
        array            $meta = []
    ): array
    {
        $user->loadMissing('family');

        return DB::transaction(function () use ($user, $data, $meta) {
            $family = $user->loadMissing('family')->family;
            $loyaltyWallet = $family->loyalty_wallet;

            if ($data->loyaltyPointsAmount > 0) {
                throw_if($loyaltyWallet->balance < $data->loyaltyPointsAmount, new InsufficientCashbackBalanceException(
                    amount: $data->loyaltyPointsAmount,
                    balance: $loyaltyWallet->balance,
                ));
            }

            $products = $this->getAvailableProductsByIds(
                ids: $data->products->toCollection()->pluck('productId'),
            );

            $children = $this->getFamilyChildrenByIds(
                user: $user,
                ids: $data->products->toCollection()->pluck('childId'),
            );

            $cart = app(Cart::class);
            $meta = [
                ...$meta,
                'gift' => $data->gift,
                'products' => [],
            ];

            $discountPerItem = 0;
            if ($data->loyaltyPointsAmount > 0) {
                $discountPerItem = floor($data->loyaltyPointsAmount / $data->products->count());
            }

            $cashbackAmount = 0;
            $passes = [];

            /** @var PassPurchaseProductData $item */
            foreach ($data->products as $item) {
                /** @var Product $product */
                $product = $products->get($item->productId);

                /** @var Child $child */
                $child = $children->get($item->childId);

                $passInfo = $this->getPassProductInfo(
                    product: $product,
                    child: $child,
                    date: $item->date,
                    discount: $discountPerItem,
                    gift: $data->gift,
                );

                $cashbackAmount += $passInfo->cashback;

                $cart = $cart->withItem(
                    product: $product,
                    pricePerItem: $passInfo->price,
                );

                $pass = new Pass();
                $pass->serial = $passInfo->pass_serial;
                $pass->remaining_time = $passInfo->remaining_time;
                $pass->is_extendable = $product->is_extendable;
                $pass->activation_date = $passInfo->activation_date;
                $pass->expires_at = $passInfo->expires_at;
                $pass->children()->associate($child);
                $pass->user()->associate($user);

                $passes[] = $pass;
                $meta['products'][] = $passInfo->toArray();
            }

            $cart = $cart->withMeta([
                ...$meta,
                'loyalty_points_used' => $data->loyaltyPointsAmount,
            ]);

            if ($data->loyaltyPointsAmount > 0) {
                $data->loyaltyPointsAmount = round($discountPerItem * $data->products->count());

                $loyaltyWallet->withdraw(
                    amount: $data->loyaltyPointsAmount,
                    meta: [
                        ...$meta,
                        'description' => 'Loyalty points redeemed successfully for pass purchase.',
                        'cart' => $cart->getMeta(),
                    ],
                );
            }

            $transfers = $family->payCart($cart);

            if ($cashbackAmount > 0) {
                $loyaltyWallet->deposit(
                    amount: $cashbackAmount,
                    meta: [
                        'name' => 'Cashback for pass purchase',
                        'transfer_ids' => array_keys($transfers),
                        'cart' => $cart->getMeta(),
                    ],
                );
            }

            foreach (array_values($transfers) as $idx => $transfer) {
                /** @var Product $product */
                $product = $transfer->deposit->payable;

                $pass = $passes[$idx];
                $pass->transfer()->associate($transfer);
                $pass->save();

                if ($transfer->withdraw) {
                    $withdrawMeta = $transfer->withdraw->meta ?? [];
                    $withdrawMeta['products'][$product->id]['pass_id'] = $pass->id;
                    $transfer->withdraw->update(['meta' => $withdrawMeta]);
                }

                if ($transfer->deposit) {
                    $depositMeta = $transfer->deposit->meta ?? [];
                    $depositMeta['products'][$product->id]['pass_id'] = $pass->id;
                    $transfer->deposit->update(['meta' => $depositMeta]);
                }

                Log::info(json_encode($pass));
            }

            return $passes;
        });
    }

    /**
     * @param Enumerable $ids
     * @return Enumerable<int, Product>
     * @throws Throwable
     */
    public function getAvailableProductsByIds(Enumerable $ids): Enumerable
    {
        $products = Product::available()
            ->whereIn(
                column: 'id',
                values: $ids,
            )
            ->get()
            ->keyBy('id');

        foreach ($products as $product) {
            throw_unless($product, new HttpException(404, 'Product not found'));
            throw_unless($product->is_available, new ProductNotAvailableException($product));
        }

        return $products;
    }

    public function getFamilyChildrenByIds(User $user, Enumerable $ids): Enumerable
    {
        $user->loadMissing('family');

        $children = Child::query()
            ->with('family')
            ->whereIn(
                column: 'id',
                values: $ids,
            )
            ->get()
            ->keyBy('id');

        foreach ($children as $child) {
            throw_unless($child, new HttpException(404, 'Children not found'));
            throw_unless(
                $child->family->is($user->family),
                new ChildrenFamilyNotAssociatedException(
                    child: $child,
                    currentFamily: $user->family,
                )
            );
        }

        return $children;
    }

    public function getPassProductInfo(
        Product  $product,
        Child    $child,
        DateTime $date = null,
        int      $discount = 0,
        bool     $gift = false,
    ): PassInfoData
    {
        $duration = CarbonInterval::minutes($product->duration_time);
        $date = ($date ? CarbonImmutable::instance($date) : CarbonImmutable::now())->startOfDay();
        $expiresAt = $date->clone()->endOfDay();

        if ($duration->totalSeconds > CarbonInterval::day()->totalSeconds) {
            $expiresAt = $expiresAt->addYear();
        }

        $price = $product->getFinalPrice($date);
        if ($discount > 0) {
            $price = max(0, $price - $discount);
        }

        $cashback = 0;
        if ($cashback > 0 && $product->cashback_percent > 0) {
            $cashback = round($price * $product->cashback_percent / 100);
        }

        if ($gift) {
            $price = 0;
            $cashback = 0;
        }

        return PassInfoData::from([
            'product_id' => $product->id,
            'price' => $price,
            'discount' => $discount,
            'cashback' => $cashback,
            'pass_serial' => static::generateSerial(),
            'child_id' => $child->id,
            'child_name' => $child->first_name . ' ' . $child->last_name,
            'activation_date' => $date,
            'expires_at' => $expiresAt,
            'remaining_time' => round($duration->totalMinutes),
            'is_extendable' => $product->is_extendable,
            'discount_price_weekday' => $product->discount_price_weekday,
            'discount_price_weekend' => $product->discount_price_weekend,
            'discount_percent' => $product->discount_percent,
            'cashback_percent' => $product->cashback_percent,
            'fee_percent' => $product->fee_percent,
            'gift' => $gift,
        ]);
    }


    public static function generateSerial(): string
    {
        return Str::upper('SN-' . Str::orderedUuid());
    }

    /**
     * @param string $serial
     * @param int $productTypeId
     * @return Pass
     * @throws Throwable
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

    public static function findPassBySerial(string $serial): Pass
    {
        return Pass::where('serial', $serial)->firstOrFail();
    }

    /**
     * @param Pass $pass
     * @param int $productTypeId
     * @return Pass
     * @throws Throwable
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

    public function isPassFeatureAvailable(Pass $pass, ProductType $productType): bool
    {
        $pass->loadMissing("transfer.deposit");
        $features = collect($pass->transfer->deposit->meta["features"] ?? []);

        return $features->has($productType->id);
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

        if ($pass->pass_package_id) {
            $pass->fill([
                'exited_at' => $now,
            ]);
        } else {
            $pass->fill([
                'exited_at' => $now,
                'remaining_time' => $pass->remaining_time - $timeLapsed,
            ]);

            if ($pass->remaining_time <= 0) {
                $pass->expires_at = $now;
            }
        }


        $pass->save();
        $pass->user->notify(new PassCheckOutNotification($pass));

        return $pass;
    }

    /**
     * @param string $serial
     * @param int $minutes
     * @return Pass
     * @throws Throwable
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

    public function getBraceletPdfPath(string $serial, bool $force = false): string
    {
        $disk = Storage::disk('local');
        $filePath = "passes/{$serial}/bracelet.pdf";
        $this->generateQRCode($serial, $force);

        abort_unless($disk->exists($filePath), 404);

        return $disk->path($filePath);
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
            ->encodeByMediaType(MediaType::IMAGE_PNG)
            ->toDataUri();

        $logoLong = Image::read(storage_path('app/public/logos/logo-long-pass.png'))
            ->rotate(-90)
            ->encodeByMediaType(MediaType::IMAGE_PNG)
            ->toDataUri();

        $logo = Image::read(storage_path('app/public/logos/logo-pass.png'))
            ->rotate(-90)
            ->encodeByMediaType(MediaType::IMAGE_PNG)
            ->toDataUri();

        $pdf = Pdf::loadView('pdf.pass-bracelet', [
            'qr' => $qr,
            'logoLong' => $logoLong,
            'logo' => $logo,
        ])->setPaper([0, 0, $this->mm2pt(25.4), $this->mm2pt(254)], 'portrait');

        $disk->put($qrImagePath, $imageContent);
        $pdf->save($braceletPdfPath, 'local');
    }

    private function mm2pt(float $mm): float
    {
        return $mm * 72 / 25.4;
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
     * @param string|null $refundComment
     * @param bool $allowUsedTickets
     * @return bool
     * @throws ExceptionInterface
     * @throws Throwable
     */
    public function refund(Pass $pass, bool $confirmed = true, string $refundComment = null, bool $allowUsedTickets = false): bool
    {
        $pass->loadMissing(['user.family', 'transfer.deposit.payable']);

        $this->isRefundable($pass, $allowUsedTickets);

        $family = $pass->user->family;
        $deposit = $pass->transfer->deposit;

        return DB::transaction(function () use ($confirmed, $deposit, $family, $pass, $refundComment) {
            /** @var Product $product */
            $product = $deposit->payable;

            $refundAmount = $deposit->amount;
            $meta = [
                'product_id' => $product->id,
                'product_name' => $product->name,
                'pass_id' => $pass->id,
                'description' => 'Refund for product "' . $product->name . '"',
                'transfer_uuid' => $pass->transfer->uuid,
            ];

            if ($refundComment) {
                $meta['reason'] = $refundComment;
                $meta['refund_date'] = now()->toISOString();

                $transferMeta = $deposit->meta ?? [];
                $transferMeta['reason'] = $refundComment;
                $transferMeta['refund_date'] = now()->toISOString();
                $deposit->update(['meta' => $transferMeta]);
            }

            $family->main_wallet->deposit($refundAmount, $meta, $confirmed);

            $pass->transfer->update(['status' => 'refund']);

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

            return true;
        });
    }

    /**
     * @param Pass $pass
     * @param bool $allowUsedTickets
     * @return bool
     * @throws Throwable
     */
    public function isRefundable(Pass $pass, bool $allowUsedTickets = false): bool
    {
        $pass->loadMissing(['transfer']);

        if (!$allowUsedTickets) {
            throw_unless(
                condition: $pass->isUnused(),
                exception: new HttpException(409, 'Refund unavailable: this ticket has already been used.'),
            );
        }

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
}
