<?php

namespace App\Filament\Widgets;

use App\Services\TransactionCancellationService;
use Bavix\Wallet\Models\Transaction;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class PaymentMethodChart extends ChartWidget
{
    protected static ?string $heading = 'Deposits by Payment Method';

    protected static ?int $sort = 2;

    protected function getData(): array
    {
        $cancellationService = app(TransactionCancellationService::class);
        $cancelledUuids = $cancellationService->getCancelledTransactionUuids();

        $cardDeposits = Transaction::where('type', 'deposit')
            ->whereJsonContains('meta->payment_method', 'card')
            ->whereNotIn('uuid', $cancelledUuids)
            ->where(function ($q) {
                $q->whereNull('meta->cancelled')
                  ->orWhere('meta->cancelled', false);
            })
            ->sum(DB::raw('amount / 100'));

        $cashDeposits = Transaction::where('type', 'deposit')
            ->whereJsonContains('meta->payment_method', 'cash')
            ->whereNotIn('uuid', $cancelledUuids)
            ->where(function ($q) {
                $q->whereNull('meta->cancelled')
                  ->orWhere('meta->cancelled', false);
            })
            ->sum(DB::raw('amount / 100'));

        return [
            'datasets' => [
                [
                    'label' => 'Deposit Amount (AED)',
                    'data' => [$cardDeposits, $cashDeposits],
                    'backgroundColor' => [
                        'rgb(59, 130, 246)',
                        'rgb(245, 158, 11)',
                    ],
                    'borderWidth' => 0,
                ],
            ],
            'labels' => ['Card Payments', 'Cash Payments'],
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'bottom',
                ],
            ],
        ];
    }
}
