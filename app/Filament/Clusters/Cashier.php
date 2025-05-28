<?php

namespace App\Filament\Clusters;

use Filament\Clusters\Cluster;
use Filament\Facades\Filament;

class Cashier extends Cluster
{
    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    public static function getNavigationLabel(): string
    {
        return 'Cashier';
    }

    public static function canAccess(): bool
    {
        return auth()->guard('admin')->user()->can('viewWalletBalance') ||
            auth()->guard('admin')->user()->can('topUpWallet') ||
            auth()->guard('admin')->user()->can('viewTickets') ||
            auth()->guard('admin')->user()->can('buyTickets') ||
            auth()->guard('admin')->user()->can('viewPasses') ||
            auth()->guard('admin')->user()->can('manageCelebrationChildren') ||
            auth()->guard('admin')->user()->can('payCelebration');
    }
}
