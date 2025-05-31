<?php

namespace App\Filament\Widgets;

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
        $loyaltyWalletIds = Wallet::where('slug', 'cashback')->pluck('id');

        $todayMainDeposits = Transaction::where('type', 'deposit')
            ->whereDate('created_at', today())
            ->whereIn('wallet_id', $mainWalletIds)
            ->sum(DB::raw('amount / 100'));

        $todayLoyaltyDeposits = Transaction::where('type', 'deposit')
            ->whereDate('created_at', today())
            ->whereIn('wallet_id', $loyaltyWalletIds)
            ->sum(DB::raw('amount / 100'));

//        $todayMainWithdrawals = Transaction::where('type', 'withdraw')
//            ->whereDate('created_at', today())
//            ->whereIn('wallet_id', $mainWalletIds)
//            ->sum(DB::raw('amount / 100'));

//        $todayLoyaltyWithdrawals = Transaction::where('type', 'withdraw')
//            ->whereDate('created_at', today())
//            ->whereIn('wallet_id', $loyaltyWalletIds)
//            ->sum(DB::raw('amount / 100'));

        $todayCardDeposits = Transaction::where('type', 'deposit')
            ->whereDate('created_at', today())
            ->whereJsonContains('meta->payment_method', 'card')
            ->whereIn('wallet_id', $mainWalletIds)
            ->sum(DB::raw('amount / 100'));

        $todayCashDeposits = Transaction::where('type', 'deposit')
            ->whereDate('created_at', today())
            ->whereJsonContains('meta->payment_method', 'cash')
            ->whereIn('wallet_id', $mainWalletIds)
            ->sum(DB::raw('amount / 100'));

        return [
            Stat::make("Today's Main Wallet Deposits", 'AED ' . number_format($todayMainDeposits, 2))
                ->description('Main wallet deposits today')
                ->descriptionIcon('heroicon-m-wallet')
                ->color('primary'),

            Stat::make("Today's Cashback Deposits", 'AED ' . number_format($todayLoyaltyDeposits, 2))
                ->description('Cashback deposits today')
                ->descriptionIcon('heroicon-m-gift')
                ->color('success'),

//            Stat::make("Today's Main Wallet Withdrawals", 'AED ' . number_format($todayMainWithdrawals, 2))
//                ->description('Main wallet withdrawals today')
//                ->descriptionIcon('heroicon-m-arrow-down-circle')
//                ->color('danger'),

//            Stat::make("Today's Cashback Withdrawals", 'AED ' . number_format($todayLoyaltyWithdrawals, 2))
//                ->description('Cashback withdrawals today')
//                ->descriptionIcon('heroicon-m-arrow-down-circle')
//                ->color('warning'),

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

        $totalMainDeposits = Transaction::where('type', 'deposit')
            ->whereIn('wallet_id', $mainWalletIds)
            ->sum(DB::raw('amount / 100'));

        $totalLoyaltyDeposits = Transaction::where('type', 'deposit')
            ->whereIn('wallet_id', $loyaltyWalletIds)
            ->sum(DB::raw('amount / 100'));

//        $totalMainWithdrawals = Transaction::where('type', 'withdraw')
//            ->whereIn('wallet_id', $mainWalletIds)
//            ->sum(DB::raw('amount / 100'));
//
//        $totalLoyaltyWithdrawals = Transaction::where('type', 'withdraw')
//            ->whereIn('wallet_id', $loyaltyWalletIds)
//            ->sum(DB::raw('amount / 100'));

        $cardDeposits = Transaction::where('type', 'deposit')
            ->whereJsonContains('meta->payment_method', 'card')
            ->whereIn('wallet_id', $mainWalletIds)
            ->sum(DB::raw('amount / 100'));

        $cashDeposits = Transaction::where('type', 'deposit')
            ->whereJsonContains('meta->payment_method', 'cash')
            ->whereIn('wallet_id', $mainWalletIds)
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

//            Stat::make('Total Main Wallet Withdrawals', 'AED ' . number_format($totalMainWithdrawals, 2))
//                ->description('All time main wallet withdrawals')
//                ->descriptionIcon('heroicon-m-arrow-trending-down')
//                ->color('danger'),
//
//            Stat::make('Total Cashback Withdrawals', 'AED ' . number_format($totalLoyaltyWithdrawals, 2))
//                ->description('All time cashback withdrawals')
//                ->descriptionIcon('heroicon-m-arrow-trending-down')
//                ->color('warning'),

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
}
