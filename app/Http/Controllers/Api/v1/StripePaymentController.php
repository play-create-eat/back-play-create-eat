<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\Family;
use Bavix\Wallet\Internal\Exceptions\ExceptionInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Stripe\Exception\ApiErrorException;
use Stripe\Stripe;
use Stripe\StripeClient;
use Stripe\Webhook;
use Symfony\Component\HttpFoundation\Response;

class StripePaymentController extends Controller
{
    /**
     * @throws ApiErrorException
     */
    public function createCheckoutSession(Request $request)
    {
        $family = auth()->guard('sanctum')->user()->family;
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0',
        ]);

        $stripe = new StripeClient(config('services.stripe.secret'));

        $amount = $validated['amount'] * 100;

        if (!$family->stripe_customer_id) {
            $customer = $stripe->customers->create([
                'email' => auth()->guard('sanctum')->user()->email,
                'name'  => $family->name,
            ]);

            $family->update(['stripe_customer_id' => $customer->id]);
        } else {
            $customer = $stripe->customers->retrieve($family->stripe_customer_id);
        }

        $ephemeralKey = $stripe->ephemeralKeys->create([
            'customer' => $customer->id,
        ], [
            'stripe_version' => '2025-02-24.acacia',
        ]);

        $paymentIntent = $stripe->paymentIntents->create([
            'amount'                    => intval($amount),
            'currency'                  => 'aed',
            'customer'                  => $customer->id,
            'automatic_payment_methods' => ['enabled' => true],
        ]);

        return response()->json([
            'paymentIntent'  => $paymentIntent->client_secret,
            'ephemeralKey'   => $ephemeralKey->secret,
            'customer'       => $customer->id,
            'publishableKey' => config('services.stripe.public')
        ]);
    }

    /**
     * @throws ExceptionInterface
     */
    public function successPayment(Request $request, Family $family)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0',
        ]);

        $amount = $validated['amount'] / 100;

        $family->mainWallet->deposit($amount);

        return response()->json([
            'message'        => 'Deposit successful',
            'wallet_balance' => $family->mainWallet->balance
        ]);
    }

    public function cancelPayment()
    {
        return response()->json(['message' => 'Payment cancelled'], Response::HTTP_BAD_REQUEST);
    }

    public function transactions()
    {
        $family = auth()->guard('sanctum')->user()->family;
        $walletTransactions = $family->mainWallet->transactions()
            ->orderBy('created_at', 'desc')
            ->get(['type', 'amount', 'meta', 'created_at']);

        $cashbackTransactions = $family->loyaltyWallet->transactions()
            ->orderBy('created_at', 'desc')
            ->get(['type', 'amount', 'meta', 'created_at']);

        return response()->json([
            'wallet_transactions'   => $walletTransactions,
            'cashback_transactions' => $cashbackTransactions
        ]);
    }

    public function balance()
    {
        $family = auth()->guard('sanctum')->user()->family;

        return response()->json([
            'wallet_balance'  => $family->mainWallet->balance,
            'cashback_points' => $family->loyaltyWallet->balance
        ]);
    }

    public function handleWebhook(Request $request)
    {
        Stripe::setApiKey(config('services.stripe.secret'));

        $endpointSecret = config('services.stripe.webhook_secret');
        $payload = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');

        try {
            $event = Webhook::constructEvent($payload, $sigHeader, $endpointSecret);

            switch ($event->type) {
                case 'payment_intent.succeeded':
                    $paymentIntent = $event->data['object'];
                    $this->handleSuccessfulPayment($paymentIntent);
                    break;

                case 'payment_intent.payment_failed':
                    $paymentIntent = $event->data['object'];
                    $this->handleFailedPayment($paymentIntent);
                    break;

                default:
                    Log::info('Unhandled Stripe event: ' . $event->type);
            }

            return response()->json(['message' => 'Webhook received'], 200);

        } catch (\Exception $e) {
            Log::error('Stripe webhook error: ' . $e->getMessage());
            return response()->json(['error' => 'Webhook handling failed'], 400);
        }
    }

    private function handleSuccessfulPayment($paymentIntent)
    {
        Log::info('Successful Payment Intent: ', $paymentIntent);

        $customerId = $paymentIntent['customer'];
        $amount = $paymentIntent['amount'] / 100;

        $family = Family::where('stripe_customer_id', $customerId)->first();

        if ($family) {
            try {
                $family->mainWallet->deposit($amount, ['description' => 'Stripe Payment']);
            } catch (ExceptionInterface $e) {
                Log::error('Main wallet deposit failed error: ' . $e->getMessage());
            }

            Log::info("Wallet updated for Family ID: $family->id, Amount: $amount");
        }
    }

    private function handleFailedPayment($paymentIntent)
    {
        Log::warning('Failed Payment Intent: ', $paymentIntent);

        $customerId = $paymentIntent['customer'];
        $family = Family::where('stripe_customer_id', $customerId)->first();

        if ($family) {
            Log::error("Payment failed for Family ID: $family->id, Amount: " . ($paymentIntent['amount'] / 100));
        }
    }
}
