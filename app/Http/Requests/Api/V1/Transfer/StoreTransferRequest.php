<?php

namespace App\Http\Requests\Api\V1\Transfer;

use App\Http\Requests\Api\V1\ApiFormRequest;
use Illuminate\Validation\Rule;

class StoreTransferRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $ownedByUser = fn ($query) => $query->where('user_id', $this->user()->id);

        return [
            'from_wallet_id' => ['required', 'integer', Rule::exists('wallets', 'id')->where($ownedByUser)],
            'to_wallet_id' => ['required', 'integer', 'different:from_wallet_id', Rule::exists('wallets', 'id')->where($ownedByUser)],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'note' => ['nullable', 'string', 'max:255'],
        ];
    }
}
