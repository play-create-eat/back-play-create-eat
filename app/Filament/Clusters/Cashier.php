<?php

namespace App\Filament\Clusters;

use Filament\Clusters\Cluster;

class Cashier extends Cluster
{
    protected static ?string $navigationIcon = 'heroicon-o-banknotes';
    
    public static function getNavigationLabel(): string
    {
        return 'Cashier';
    }
}
