<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\Family;
use Bavix\Wallet\Internal\Exceptions\ExceptionInterface;
use Illuminate\Http\Request;
use Stripe\Checkout\Session;
use Stripe\Customer;
use Stripe\Exception\ApiErrorException;
use Stripe\Stripe;
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

        $amount = $validated['amount'] * 100;

        Stripe::setApiKey(config('services.stripe.secret'));

        if (!$family->stripe_customer_id) {
            $customer = Customer::create([
                'email' => auth()->guard('sanctum')->user()->email,
                'name'  => $family->name,
            ]);

            $family->update(['stripe_customer_id' => $customer->id]);
        }

        $session = Session::create([
            'payment_method_types' => ['card'],
            'customer'             => $family->stripe_customer_id,
            'line_items'           => [
                [
                    'price_data' => [
                        'currency'     => 'aed',
                        'product_data' => [
                            'name' => 'Wallet Deposit',
                        ],
                        'unit_amount'  => $amount,
                    ],
                    'quantity'   => 1,
                ],
            ],
            'mode'                 => 'payment',
            'success_url'          => route('stripe.success', $family),
            'cancel_url'           => route('stripe.cancel'),
        ]);

        return response()->json(['id' => $session->id]);
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

    public function transactions(Family $family)
    {
        $walletTransactions = $family->mainWallet->transactions()
            ->orderBy('created_at', 'desc')
            ->get(['type', 'amount', 'meta', 'created_at']);

        $cashbackTransactions = $family->loyaltyWallet->transactions()
            ->orderBy('created_at', 'desc')
            ->get(['type', 'amount', 'meta', 'created_at']);

        return response()->json([
            'wallet_transactions' => $walletTransactions,
            'cashback_transactions' => $cashbackTransactions
        ]);
    }

    public function balance()
    {
        $family = auth()->guard('sanctum')->user()->family;

        return response()->json([
            'wallet_balance' => $family->mainWallet->balance,
            'cashback_balance' => $family->loyaltyWallet->balance
        ]);
    }
}
