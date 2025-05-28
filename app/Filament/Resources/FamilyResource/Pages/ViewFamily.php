<?php

namespace App\Filament\Resources\FamilyResource\Pages;

use App\Filament\Resources\FamilyResource;
use Bavix\Wallet\Models\Transaction;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ViewRecord;

class ViewFamily extends ViewRecord
{
    protected static string $resource = FamilyResource::class;

    public function getTabs(): array
    {
        return [
            'details' => Tab::make('Details'),
            'users' => Tab::make('Users')
                ->relationship('users'),
            'children' => Tab::make('Children')
                ->relationship('children'),
            'wallet_transactions' => Tab::make('Wallet Transactions')
                ->relationship('users')
                ->label('Wallet Transactions')
                ->badge(function () {
                    $family = $this->getRecord();
                    $mainWalletId = $family->main_wallet?->id;
                    $loyaltyWalletId = $family->loyalty_wallet?->id;

                    if (!$mainWalletId && !$loyaltyWalletId) {
                        return 0;
                    }

                    return Transaction::query()
                        ->where(function ($query) use ($mainWalletId, $loyaltyWalletId) {
                            if ($mainWalletId) {
                                $query->where('wallet_id', $mainWalletId);
                            }
                            if ($loyaltyWalletId) {
                                $query->orWhere('wallet_id', $loyaltyWalletId);
                            }
                        })
                        ->count();
                }),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
        ];
    }

}
