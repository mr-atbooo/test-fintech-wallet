<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Transaction */
class TransactionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'wallet_id' => $this->wallet_id,
            'wallet' => WalletResource::make($this->whenLoaded('wallet')),
            'category' => CategoryResource::make($this->whenLoaded('category')),
            'transfer_id' => $this->transfer_id,
            'type' => $this->type->value,
            'amount' => (float) $this->amount,
            'balance_before' => (float) $this->balance_before,
            'balance_after' => (float) $this->balance_after,
            'note' => $this->note,
            'transaction_date' => $this->transaction_date->toIso8601String(),
            'status' => $this->status->value,
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
