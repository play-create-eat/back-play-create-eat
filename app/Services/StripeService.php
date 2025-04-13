<?php

namespace App\Services;

use App\Models\Family;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Stripe\Exception\ApiErrorException;
use Stripe\StripeClient;

class StripeService
{
    private StripeClient $stripeClient;

    private ?object $costumer = null;
    private ?object $ephemeralKey = null;

    public function __construct()
    {
        $this->stripeClient = new StripeClient(config('services.stripe.secret'));
    }

    public function walletTopUpSession(Request $request, Family $family): Collection|string
    {
        $validated = $request->validate([
            'amount' => 'required|integer|min:0',
        ]);

        if (!$family->stripe_customer_id) {
            $family->update([
                'stripe_customer_id' => $this->getStripeCostumer($family)->id,
            ]);
        }

        $ephemeralKey = $this->getEphemeralKey($family);
        try {
            $paymentIntent = $this->stripeClient->paymentIntents->create([
                'amount'                    => $validated['amount'],
                'currency'                  => 'aed',
                'customer'                  => $this->getStripeCostumer($family)->id,
                'automatic_payment_methods' => ['enabled' => true],
                'description'               => 'Wallet top-up',
                'metadata'                  => [
                    'family_id' => $family->id,
                ]
            ]);
        } catch (ApiErrorException $exception) {
            return $exception->getMessage();
        }

        return collect([
            'clientSecret'   => $paymentIntent->client_secret,
            'ephemeralKey'   => $ephemeralKey->secret,
            'customer'       => $this->getStripeCostumer($family)->id,
            'publishableKey' => config('services.stripe.public'),
        ]);
    }

    private function getStripeCostumer(Family $family)
    {
        if ($this->costumer) {
            return $this->costumer;
        }

        try {
            if ($family->stripe_customer_id) {
                $this->costumer = $this->stripeClient->customers->retrieve($family->stripe_customer_id);
                return $this->costumer;
            }

            $this->costumer = $this->stripeClient->customers->create([
                'email' => auth()->guard('sanctum')->user()->email,
                'name'  => $family->name,
            ]);

            $family->update(['stripe_customer_id' => $this->costumer->id]);

            return $this->costumer;
        } catch (ApiErrorException $exception) {
            return $exception->getMessage();
        }

    }

    private function getEphemeralKey(Family $family)
    {
        if ($this->ephemeralKey) {
            return $this->ephemeralKey;
        }

        try {
            $this->ephemeralKey = $this->stripeClient->ephemeralKeys->create([
                'customer' => $this->getStripeCostumer($family)->id,
            ], [
                'stripe_version' => '2025-02-24.acacia',
            ]);

            return $this->ephemeralKey;

        } catch (ApiErrorException $exception) {
            return $exception->getMessage();
        }

    }

    public function paymentSession(Payment $payment, Request $request): Collection|string
    {
        $validated = $request->validate([
            'cashback_amount' => 'required|integer|min:0',
        ]);

        $payment->load('payable');

        if ($payment->family->loyalty_wallet->balance < $validated['cashback_amount']) {
            return response()->json([
                'message' => 'Insufficient star points'
            ], 422);
        }

        try {
            $ephemeralKey = $this->getEphemeralKey($payment->family);

            $paymentIntent = $this->stripeClient->paymentIntents->create([
                'amount'                    => $payment->amount - $validated['cashback_amount'],
                'currency'                  => 'aed',
                'customer'                  => $this->getStripeCostumer($payment->family)->id,
                'automatic_payment_methods' => ['enabled' => true],
                'description'               => "Top-up for payment #$payment->id",
                'metadata'                  => [
                    'payment_id'      => $payment->id,
                    'cashback_amount' => $validated['cashback_amount'],
                ]
            ]);
        } catch (ApiErrorException $exception) {
            return $exception->getMessage();
        }

        return collect([
            'paymentIntentId' => $paymentIntent->id,
            'clientSecret'    => $paymentIntent->client_secret,
            'ephemeralKey'    => $ephemeralKey->secret,
            'customer'        => $this->getStripeCostumer($payment->family)->id,
            'publishableKey'  => config('services.stripe.public')
        ]);
    }
}
