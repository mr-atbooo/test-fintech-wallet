<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Wallet */
class WalletResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'wallet_type' => WalletTypeResource::make($this->whenLoaded('walletType')),
            'currency' => $this->currency,
            'balance' => (float) $this->balance,
            'status' => $this->status->value,
            'is_default' => $this->is_default,
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}
