<?php

namespace App\Models;

use App\Enums\WalletStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Wallet extends Model
{
    use SoftDeletes;

    protected $attributes = [
        'status' => WalletStatus::Active->value,
        'is_default' => false,
    ];

    protected $fillable = [
        'user_id',
        'wallet_type_id',
        'name',
        'currency',
        'balance',
        'status',
        'is_default',
    ];

    protected function casts(): array
    {
        return [
            'balance' => 'decimal:2',
            'status' => WalletStatus::class,
            'is_default' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function walletType(): BelongsTo
    {
        return $this->belongsTo(WalletType::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function outgoingTransfers(): HasMany
    {
        return $this->hasMany(Transfer::class, 'from_wallet_id');
    }

    public function incomingTransfers(): HasMany
    {
        return $this->hasMany(Transfer::class, 'to_wallet_id');
    }
}
