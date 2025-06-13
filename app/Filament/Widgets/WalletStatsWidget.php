<?php

namespace App\Filament\Widgets;

use App\Models\Family;
use Bavix\Wallet\Models\Transaction;
use Bavix\Wallet\Models\Wallet;
use Filament\Facades\Filament;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class WalletStatsWidget extends BaseWidget
{
    protected int|string|array $columnSpan = 'full';

    protected function getColumns(): int
    {
        return 3;
    }

    protected function getStats(): array
    {
        $user = Filament::auth()->user();

        $stats = [];

        if ($user->can('viewTodayAnalytics')) {
            $stats = array_merge($stats, $this->getTodayStats());
        }

        if ($user->can('viewFullAnalytics')) {
            $stats = array_merge($stats, $this->getFullAnalyticsStats());
        }

        return $stats;
    }

    protected function getTodayStats(): array
    {
        $mainWalletIds = Wallet::where('slug', 'default')->pluck('id');

        $refundedTransactionUuids = $this->getRefundedTransactionUuids(today());

        $cancelledTransactionUuids = $this->getCancelledTransactionUuids(today());

        $todayMainDeposits = Transaction::where('type', 'deposit')
            ->whereDate('created_at', today())
            ->whereIn('wallet_id', $mainWalletIds)
            ->where('payable_type', Family::class)
            ->whereNotIn('uuid', $refundedTransactionUuids)
            ->whereNotIn('uuid', $cancelledTransactionUuids)
            ->where(function ($q) {
                $q->whereNull('meta->cancelled')
                  ->orWhere('meta->cancelled', false);
            })
            ->sum(DB::raw('amount / 100'));

        $todayAppMainWalletDeposits = Transaction::where('type', 'deposit')
            ->whereDate('created_at', today())
            ->whereJsonContains('meta->description', 'Stripe Payment')
            ->whereIn('wallet_id', $mainWalletIds)
            ->where('payable_type', Family::class)
            ->whereNotIn('uuid', $refundedTransactionUuids)
            ->whereNotIn('uuid', $cancelledTransactionUuids)
            ->where(function ($q) {
                $q->whereNull('meta->cancelled')
                  ->orWhere('meta->cancelled', false);
            })
            ->sum(DB::raw('amount / 100'));

        $todayCardDeposits = Transaction::where('type', 'deposit')
            ->whereDate('created_at', today())
            ->whereJsonContains('meta->payment_method', 'card')
            ->whereIn('wallet_id', $mainWalletIds)
            ->where('payable_type', Family::class)
            ->whereNotIn('uuid', $refundedTransactionUuids)
            ->whereNotIn('uuid', $cancelledTransactionUuids)
            ->where(function ($q) {
                $q->whereNull('meta->cancelled')
                  ->orWhere('meta->cancelled', false);
            })
            ->sum(DB::raw('amount / 100'));

        $todayCashDeposits = Transaction::where('type', 'deposit')
            ->whereDate('created_at', today())
            ->whereJsonContains('meta->payment_method', 'cash')
            ->whereIn('wallet_id', $mainWalletIds)
            ->where('payable_type', Family::class)
            ->whereNotIn('uuid', $refundedTransactionUuids)
            ->whereNotIn('uuid', $cancelledTransactionUuids)
            ->where(function ($q) {
                $q->whereNull('meta->cancelled')
                  ->orWhere('meta->cancelled', false);
            })
            ->sum(DB::raw('amount / 100'));

        return [
            Stat::make("Today's Main Wallet Deposits", 'AED ' . number_format($todayMainDeposits, 2))
                ->description('Main wallet deposits today')
                ->descriptionIcon('heroicon-m-wallet')
                ->color('primary'),

            Stat::make("Today's App Main Wallet Deposits", 'AED ' . number_format($todayAppMainWalletDeposits, 2))
                ->description('App main wallet deposits today')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success'),

            Stat::make('Today Card Deposits', 'AED ' . number_format($todayCardDeposits, 2))
                ->description('Card deposits made today')
                ->descriptionIcon('heroicon-m-credit-card')
                ->color('info'),

            Stat::make('Today Cash Deposits', 'AED ' . number_format($todayCashDeposits, 2))
                ->description('Cash deposits made today')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('secondary')
        ];
    }

    protected function getFullAnalyticsStats(): array
    {
        $mainWalletIds = Wallet::where('slug', 'default')->pluck('id');
        $loyaltyWalletIds = Wallet::where('slug', 'cashback')->pluck('id');

        $refundedTransactionUuids = $this->getRefundedTransactionUuids();

        $cancelledTransactionUuids = $this->getCancelledTransactionUuids();

        $totalMainDeposits = Transaction::where('type', 'deposit')
            ->whereIn('wallet_id', $mainWalletIds)
            ->where('payable_type', Family::class)
            ->whereNotIn('uuid', $refundedTransactionUuids)
            ->whereNotIn('uuid', $cancelledTransactionUuids)
            ->where(function ($q) {
                $q->whereNull('meta->cancelled')
                  ->orWhere('meta->cancelled', false);
            })
            ->sum(DB::raw('amount / 100'));

        $totalLoyaltyDeposits = Transaction::where('type', 'deposit')
            ->whereIn('wallet_id', $loyaltyWalletIds)
            ->where('payable_type', Family::class)
            ->sum(DB::raw('amount / 100'));

        $cardDeposits = Transaction::where('type', 'deposit')
            ->whereJsonContains('meta->payment_method', 'card')
            ->whereIn('wallet_id', $mainWalletIds)
            ->where('payable_type', Family::class)
            ->whereNotIn('uuid', $refundedTransactionUuids)
            ->whereNotIn('uuid', $cancelledTransactionUuids)
            ->where(function ($q) {
                $q->whereNull('meta->cancelled')
                  ->orWhere('meta->cancelled', false);
            })
            ->sum(DB::raw('amount / 100'));

        $cashDeposits = Transaction::where('type', 'deposit')
            ->whereJsonContains('meta->payment_method', 'cash')
            ->whereIn('wallet_id', $mainWalletIds)
            ->where('payable_type', Family::class)
            ->whereNotIn('uuid', $refundedTransactionUuids)
            ->whereNotIn('uuid', $cancelledTransactionUuids)
            ->where(function ($q) {
                $q->whereNull('meta->cancelled')
                  ->orWhere('meta->cancelled', false);
            })
            ->sum(DB::raw('amount / 100'));

        return [
            Stat::make('Total Main Wallet Deposits', 'AED ' . number_format($totalMainDeposits, 2))
                ->description('All time main wallet top-ups')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success'),

            Stat::make('Total Cashback Earned', 'AED ' . number_format($totalLoyaltyDeposits, 2))
                ->description('All time cashback earned')
                ->descriptionIcon('heroicon-m-gift')
                ->color('primary'),

            Stat::make('Card Payments', 'AED ' . number_format($cardDeposits, 2))
                ->description('Total deposits via card')
                ->descriptionIcon('heroicon-m-credit-card')
                ->color('info'),

            Stat::make('Cash Payments', 'AED ' . number_format($cashDeposits, 2))
                ->description('Total deposits via cash')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('secondary'),
        ];
    }

    /**
     * Get UUIDs of transactions that have been refunded
     */
    private function getRefundedTransactionUuids($date = null): array
    {
        $query = Transaction::where('type', 'withdraw')
            ->whereJsonContains('meta->description', 'Refund')
            ->whereNotNull('meta->transfer_uuid');

        if ($date) {
            $query->whereDate('created_at', $date);
        }

        return $query->pluck(DB::raw("meta->>'transfer_uuid'"))
            ->filter()
            ->toArray();
    }

    /**
     * Get UUIDs of transactions that have been cancelled
     */
    private function getCancelledTransactionUuids($date = null): array
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
}
