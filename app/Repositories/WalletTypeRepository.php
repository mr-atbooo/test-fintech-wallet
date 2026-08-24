<?php

namespace App\Repositories;

use App\Enums\WalletTypeCode;
use App\Models\WalletType;

class WalletTypeRepository
{
    public function findByCode(WalletTypeCode $code): ?WalletType
    {
        return WalletType::where('code', $code->value)->first();
    }
}
