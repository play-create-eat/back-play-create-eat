<?php

namespace App\Filament\Widgets;

use Bavix\Wallet\Models\Transaction;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class WalletStatsWidget extends BaseWidget
{
    protected int|string|array $columnSpan = 'full';

    protected function getColumns(): int
    {
        return 2;
    }

    protected function getStats(): array
    {
        $totalDeposits = Transaction::where('type', 'deposit')
            ->sum(DB::raw('amount / 100'));

        $totalWithdrawals = Transaction::where('type', 'withdraw')
            ->sum(DB::raw('amount / 100'));

        $cardDeposits = Transaction::where('type', 'deposit')
            ->whereJsonContains('meta->payment_method', 'card')
            ->sum(DB::raw('amount / 100'));

        $todayCardDeposits = Transaction::where('type', 'deposit')
            ->whereDate('created_at', today())
            ->whereJsonContains('meta->payment_method', 'card')
            ->sum(DB::raw('amount / 100'));

        $todayCashDeposits = Transaction::where('type', 'deposit')
            ->whereDate('created_at', today())
            ->whereJsonContains('meta->payment_method', 'cash')
            ->sum(DB::raw('amount / 100'));

        $cashDeposits = Transaction::where('type', 'deposit')
            ->whereJsonContains('meta->payment_method', 'cash')
            ->sum(DB::raw('amount / 100'));

        $todayDeposits = Transaction::where('type', 'deposit')
            ->whereDate('created_at', today())
            ->sum(DB::raw('amount / 100'));

        $todayWithdrawals = Transaction::where('type', 'withdraw')
            ->whereDate('created_at', today())
            ->sum(DB::raw('amount / 100'));

        return [
            Stat::make('Total Deposits', 'AED ' . number_format($totalDeposits, 2))
                ->description('All time wallet top-ups')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success'),

            Stat::make('Total Withdrawals', 'AED ' . number_format($totalWithdrawals, 2))
                ->description('All time wallet withdrawals')
                ->descriptionIcon('heroicon-m-arrow-trending-down')
                ->color('danger'),

            Stat::make('Card Payments', 'AED ' . number_format($cardDeposits, 2))
                ->description('Total deposits via card')
                ->descriptionIcon('heroicon-m-credit-card')
                ->color('info'),

            Stat::make('Cash Payments', 'AED ' . number_format($cashDeposits, 2))
                ->description('Total deposits via cash')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('warning'),

            Stat::make("Today's Deposits", 'AED ' . number_format($todayDeposits, 2))
                ->description('Deposits made today')
                ->descriptionIcon('heroicon-m-calendar-days')
                ->color('primary'),

            Stat::make("Today's Withdrawals", 'AED ' . number_format($todayWithdrawals, 2))
                ->description('Withdrawals made today')
                ->descriptionIcon('heroicon-m-calendar-days')
                ->color('secondary'),

            Stat::make('Today Card Deposits', 'AED ' . number_format($todayCardDeposits, 2))
                ->description('Card deposits made today')
                ->descriptionIcon('heroicon-m-credit-card')
                ->color('info'),

            Stat::make('Today Cash Deposits', 'AED ' . number_format($todayCashDeposits, 2))
                ->description('Cash deposits made today')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('warning'),
        ];
    }
}
