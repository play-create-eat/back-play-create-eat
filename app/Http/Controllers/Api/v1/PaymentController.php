<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\Celebration;
use App\Models\Payment;
use App\Services\StripeService;
use DB;
use Illuminate\Http\Request;
use Throwable;

class PaymentController extends Controller
{
    public function store(Request $request, Celebration $celebration)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric:min:0',
        ]);

        $payment = Payment::firstOrCreate([
            'family_id'    => auth()->guard('sanctum')->user()->family->id,
            'payable_type' => Celebration::class,
            'payable_id'   => $celebration->id,
            'amount'       => $validated['amount'],
            'status'       => 'pending',
        ]);

        return response()->json($payment);
    }

    public function pay(Request $request, Payment $payment)
    {
        $validated = $request->validate([
            'cashback_amount' => 'required|integer|min:0'
        ]);

        if ($payment->family->loyalty_wallet->balance < $validated['cashback_amount']) {
            return response()->json([
                'message' => 'Insufficient star points'
            ], 422);
        }

        $total = $payment->amount - $validated['cashback_amount'];

        if ($payment->family->main_wallet->balance < $total) {
            return response()->json([
                'message' => 'Insufficient funds'
            ], 422);
        }

        try {
            DB::transaction(function () use ($payment, $validated, $total) {
                $payment->family->loyalty_wallet->withdraw($validated['cashback_amount'], [
                    'name' => 'Cashback payment for ' . $payment->payable_type,
                    'id'   => $payment->id,
                ]);

                $payment->family->main_wallet->withdraw($total, [
                    'name' => 'Payment for ' . $payment->payable_type,
                    'id'   => $payment->id,
                ]);

                if ($payment->payable instanceof Celebration) {
                    $payment->family->loyalty_wallet->deposit(
                        $total * $payment->payable->package->cashback_percentage / 100,
                        [
                            'name' => 'Cashback for ' . $payment->payable_type,
                            'id'   => $payment->id,
                        ]
                    );
                }
            });

        } catch (Throwable $e) {
            return response()->json([
                'message' => 'Error processing payment: ' . $e->getMessage()
            ], 422);
        }

        $payment->update([
            'status' => 'paid'
        ]);

        $payment->payable->update([
            'paid_amount' => $payment->amount,
            'completed'   => true
        ]);

        return response()->json($payment);
    }

    public function cardPay(Request $request, Payment $payment, StripeService $stripeService)
    {
        return response()->json($stripeService->paymentSession($payment, $request));
    }
}
