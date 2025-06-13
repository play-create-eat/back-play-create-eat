<?php

namespace App\Policies\Filament;

use App\Models\Admin;
use App\Models\User;

class TransactionPolicy
{
    public function viewTransactions(Admin $admin): bool
    {
        return $admin->hasPermissionTo('view-wallet-transactions');
    }

    public function cancelTransactions(Admin $admin): bool
    {
        return $admin->hasPermissionTo('cancel-wallet-transactions');
    }
}
