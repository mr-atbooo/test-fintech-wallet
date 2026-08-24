<?php

namespace App\Http\Requests\Api\V1\Wallet;

use App\Enums\WalletStatus;
use App\Http\Requests\Api\V1\ApiFormRequest;
use Illuminate\Validation\Rule;

class StoreWalletRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'wallet_type_id' => ['required', 'integer', 'exists:wallet_types,id'],
            'name' => ['required', 'string', 'max:255'],
            'currency' => ['nullable', 'string', 'size:3'],
            'status' => ['nullable', Rule::enum(WalletStatus::class)],
            'is_default' => ['nullable', 'boolean'],
        ];
    }
}
