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
        try {
            if ($family->stripe_customer_id) {
                return $this->stripeClient->customers->retrieve($family->stripe_customer_id);
            }

            return $this->stripeClient->customers->create([
                'email' => auth()->guard('sanctum')->user()->email,
                'name'  => $family->name,
            ]);
        } catch (ApiErrorException $exception) {
            return $exception->getMessage();
        }

    }

    private function getEphemeralKey(Family $family)
    {
        try {
            return $this->stripeClient->ephemeralKeys->create([
                'customer' => $this->getStripeCostumer($family)->id,
            ], [
                'stripe_version' => '2025-02-24.acacia',
            ]);
        } catch (ApiErrorException $exception) {
            return $exception->getMessage();
        }

    }

    public function paymentSession(Payment $payment, Request $request): Collection|string
    {
        $validated = $request->validate([
            'cashback_amount' => 'required|integer|min:1',
        ]);
        $payment->load('payable');

        try {
            $ephemeralKey = $this->getEphemeralKey($payment->family);

            $paymentIntent = $this->stripeClient->paymentIntents->create([
                'amount'                    => $payment->amount,
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
