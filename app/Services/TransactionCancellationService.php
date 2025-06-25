<?php

namespace App\Services;

use Bavix\Wallet\Internal\Exceptions\ExceptionInterface;
use Bavix\Wallet\Models\Transaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class TransactionCancellationService
{
    /**
     * Cancel a deposit transaction
     *
     * @param Transaction $transaction
     * @param string|null $reason
     * @param bool $confirmed
     * @return bool
     * @throws ExceptionInterface
     */
    public function cancelDeposit(Transaction $transaction, string $reason = null, bool $confirmed = true): bool
    {
        // Validate that this is a cancellable deposit
        $this->validateCancellable($transaction);

        return DB::transaction(function () use ($transaction, $reason, $confirmed) {
            // Get the family/payable entity
            $payable = $transaction->payable;

            // Prepare metadata for the cancellation
            $cancelMeta = [
                'description' => 'Cancellation of deposit transaction',
                'original_transaction_uuid' => $transaction->uuid,
                'cancelled_at' => now()->toISOString(),
                'cancelled_by' => auth()->guard('admin')->id(),
                'cancellation_reason' => $reason,
            ];

            // Copy original transaction metadata
            if ($transaction->meta) {
                $cancelMeta = array_merge($transaction->meta, $cancelMeta);
                $cancelMeta['description'] = 'Cancellation: ' . ($transaction->meta['description'] ?? 'Deposit transaction');
            }

            // Create a withdrawal transaction to reverse the deposit
            $wallet = $transaction->wallet;
            $reversalTransaction = $wallet->withdraw(
                amount: $transaction->amount,
                meta: $cancelMeta,
                confirmed: $confirmed
            );

            // Mark the original transaction as cancelled in its metadata
            $originalMeta = $transaction->meta ?? [];
            $originalMeta['cancelled'] = true;
            $originalMeta['cancelled_at'] = now()->toISOString();
            $originalMeta['cancelled_by'] = auth()->guard('admin')->id();
            $originalMeta['cancellation_reason'] = $reason;
            $originalMeta['reversal_transaction_uuid'] = $reversalTransaction->uuid;

            $transaction->update(['meta' => $originalMeta]);

            Log::info('Transaction cancelled', [
                'original_transaction_id' => $transaction->uuid,
                'reversal_transaction_id' => $reversalTransaction->uuid,
                'amount' => $transaction->amount / 100,
                'payment_method' => $transaction->meta['payment_method'] ?? 'unknown',
                'cancelled_by' => auth()->guard('admin')->id(),
                'reason' => $reason,
            ]);

            return true;
        });
    }

    /**
     * Cancel multiple deposit transactions
     *
     * @param array $transactionIds
     * @param string|null $reason
     * @param bool $confirmed
     * @return array
     */
    public function cancelMultipleDeposits(array $transactionIds, string $reason = null, bool $confirmed = true): array
    {
        $results = [
            'successful' => [],
            'failed' => [],
        ];

        foreach ($transactionIds as $transactionId) {
            try {
                $transaction = Transaction::findOrFail($transactionId);
                $this->cancelDeposit($transaction, $reason, $confirmed);
                $results['successful'][] = $transactionId;
            } catch (\Exception $e) {
                $results['failed'][] = [
                    'id' => $transactionId,
                    'error' => $e->getMessage(),
                ];
            }
        }

        return $results;
    }

    /**
     * Check if a transaction can be cancelled
     *
     * @param Transaction $transaction
     * @return bool
     * @throws \Exception
     */
    public function isCancellable(Transaction $transaction): bool
    {
        try {
            $this->validateCancellable($transaction);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Validate that a transaction can be cancelled
     *
     * @param Transaction $transaction
     * @throws \Exception
     */
    protected function validateCancellable(Transaction $transaction): void
    {
        // Only allow cancellation of deposit transactions
        if ($transaction->type !== 'deposit') {
            throw new \Exception('Only deposit transactions can be cancelled.');
        }

        // Check if transaction is already cancelled
        if (isset($transaction->meta['cancelled']) && $transaction->meta['cancelled']) {
            throw new \Exception('This transaction has already been cancelled.');
        }

        // Only allow cancellation of cash and card deposits
        $paymentMethod = $transaction->meta['payment_method'] ?? null;
        if (!in_array($paymentMethod, ['cash', 'card'])) {
            throw new \Exception('Only cash and card deposit transactions can be cancelled.');
        }

        // Check if the wallet has sufficient balance for reversal
        if ($transaction->wallet->balance < $transaction->amount) {
            throw new \Exception('Insufficient wallet balance to cancel this transaction.');
        }

        // Optional: Add time-based restrictions (e.g., can only cancel within 24 hours)
        $transactionAge = $transaction->created_at->diffInHours(now());
        if ($transactionAge > 24) {
            throw new \Exception('Cannot cancel transactions older than 24 hours.');
        }
    }

    /**
     * Get cancelled transaction UUIDs
     *
     * @param \Carbon\Carbon|null $date
     * @return array
     */
    public function getCancelledTransactionUuids(\Carbon\Carbon $date = null): array
    {
        $query = Transaction::where('type', 'withdraw')
            ->whereJsonContains('meta->description', 'Cancellation')
            ->whereNotNull('meta->original_transaction_uuid');

        if ($date) {
            $query->whereDate('created_at', $date);
        }

        return $query->pluck(DB::raw("meta->>'original_transaction_uuid'"))
            ->filter()
            ->toArray();
    }

    /**
     * Check if a transaction is cancelled
     *
     * @param Transaction $transaction
     * @return bool
     */
    public function isCancelled(Transaction $transaction): bool
    {
        return isset($transaction->meta['cancelled']) && $transaction->meta['cancelled'] === true;
    }
}