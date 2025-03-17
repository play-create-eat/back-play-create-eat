<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\Family;
use Bavix\Wallet\Internal\Exceptions\ExceptionInterface;
use Illuminate\Http\Request;

class WalletController extends Controller
{
    public function balance(Family $family)
    {
        return response()->json([
            'wallet_balance' => $family->mainWallet->balance,
            'loyalty_balance' => $family->loyaltyWallet->balance
        ]);
    }

    /**
     * @throws ExceptionInterface
     */
    public function deposit(Request $request, Family $family)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0',
        ]);

        $amount = $validated['amount'];

        $family->wallet->deposit($amount);

        return response()->json(['message' => 'Funds added successfully', 'balance' => $family->wallet->balance]);
    }
}
