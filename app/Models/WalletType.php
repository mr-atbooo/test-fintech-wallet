<?php

namespace App\Models;

use App\Enums\Status;
use App\Enums\WalletTypeCode;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WalletType extends Model
{
    protected $attributes = [
        'status' => Status::Active->value,
    ];

    protected $fillable = [
        'name',
        'code',
        'description',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'code' => WalletTypeCode::class,
            'status' => Status::class,
        ];
    }

    public function wallets(): HasMany
    {
        return $this->hasMany(Wallet::class);
    }
}
