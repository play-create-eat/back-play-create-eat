<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\Celebration;
use App\Models\Family;
use App\Models\Payment;
use App\Services\StripeService;
use Bavix\Wallet\Internal\Exceptions\ExceptionInterface;
use DB;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Stripe\Stripe;
use Stripe\Webhook;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class StripePaymentController extends Controller
{
    public function createCheckoutSession(Request $request, StripeService $stripeService)
    {
        return response()->json($stripeService->walletTopUpSession($request, auth()->guard('sanctum')->user()->family));
    }

    /**
     * @throws ExceptionInterface
     */
    public function successPayment(Request $request, Family $family)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0',
        ]);

        $amount = $validated['amount'];

        $family->main_wallet->deposit($amount);

        return response()->json([
            'message'        => 'Deposit successful',
            'wallet_balance' => $family->loyalty_wallet->balance
        ]);
    }

    public function cancelPayment()
    {
        return response()->json(['message' => 'Payment cancelled'], Response::HTTP_BAD_REQUEST);
    }

    public function transactions()
    {
        $family = auth()->guard('sanctum')->user()->family;
        $walletTransactions = $family->main_wallet->walletTransactions()
            ->orderBy('created_at', 'desc')
            ->get(['type', 'amount', 'meta', 'created_at']);

        $cashbackTransactions = $family->loyalty_wallet->walletTransactions()
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
            'wallet_balance'  => $family->main_wallet->balance,
            'cashback_points' => $family->loyalty_wallet->balance
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

            return response()->json(['message' => 'Webhook received']);

        } catch (Exception $e) {
            Log::error('Stripe webhook error: ' . $e->getMessage());
            return response()->json(['error' => 'Webhook handling failed'], 400);
        }
    }

    private function handleSuccessfulPayment($paymentIntent)
    {
        $customerId = $paymentIntent['customer'];
        $family = Family::where('stripe_customer_id', $customerId)->first();

        if (!$family) {
            return;
        }

        $amount = $paymentIntent['amount'];
        $payment = Payment::find($paymentIntent['metadata']['payment_id']);
        try {
            $family->main_wallet->deposit($amount, ['description' => 'Stripe Payment']);
            if (!$payment) {
                return;
            }

            Log::info("Payment exists for Family ID: $family->id, Amount: $amount");

            if ($payment->payable instanceof Celebration) {
                Log::info("Payment is Celebration");
                DB::transaction(function () use ($payment, $paymentIntent) {
                    $total = $payment->amount - $paymentIntent['metadata']['cashback_amount'];
                    Log::info("Total: $total");

                    $payment->family->loyalty_wallet->withdraw($paymentIntent['metadata']['cashback_amount'], [
                        'name' => 'Cashback payment for ' . $payment->payable_type,
                        'id'   => $payment->id,
                    ]);

                    Log::info("Cashback withdrawn: " . $paymentIntent['metadata']['cashback_amount']);

                    $payment->family->main_wallet->withdraw($total, [
                        'name' => 'Payment for ' . $payment->payable_type,
                        'id'   => $payment->id,
                    ]);

                    Log::info("Main wallet withdrawn: " . $total);

                    $payment->family->loyalty_wallet->deposit(
                        $total * $payment->payable->package->cashback_percentage / 100,
                        [
                            'name' => 'Cashback for ' . $payment->payable_type,
                            'id'   => $payment->id,
                        ]
                    );

                    Log::info("Cashback deposited: " . ($total * $payment->payable->package->cashback_percentage / 100));

                    $payment->update([
                        'status' => 'paid'
                    ]);
                    $payment->payable->update([
                        'paid_amount' => $payment->amount,
                        'completed'   => true
                    ]);

                    Log::info("Payment updated for Celebration ID: $payment->payable_id, Amount: $payment->amount");
                });
                Log::info("Payment successful for Celebration ID: $payment->payable_id, Amount: $amount");
            }
        } catch (ExceptionInterface|Throwable $e) {
            Log::error('Main wallet deposit failed error: ' . $e->getMessage());
        }

        Log::info("Wallet updated for Family ID: $family->id, Amount: $amount");
    }

    private function handleFailedPayment($paymentIntent)
    {
        Log::warning('Failed Payment Intent: ', $paymentIntent->toArray());

        $customerId = $paymentIntent['customer'];
        $family = Family::where('stripe_customer_id', $customerId)->first();

        if ($family) {
            Log::error("Payment failed for Family ID: $family->id, Amount: " . ($paymentIntent['amount']));
        }
    }
}
