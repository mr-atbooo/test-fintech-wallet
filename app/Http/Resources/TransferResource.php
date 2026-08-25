<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Transfer */
class TransferResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'from_wallet' => WalletResource::make($this->whenLoaded('fromWallet')),
            'to_wallet' => WalletResource::make($this->whenLoaded('toWallet')),
            'amount' => (float) $this->amount,
            'note' => $this->note,
            'status' => $this->status->value,
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
