<?php

namespace App\Enums;

enum WalletTypeCode: string
{
    case Cash = 'cash';
    case Bank = 'bank';
    case Savings = 'savings';
    case Credit = 'credit';

    /**
     * Wallet types that must never carry a negative balance.
     */
    public function allowsNegativeBalance(): bool
    {
        return $this === self::Credit;
    }
}
