<?php

namespace App\Http\Requests\Api\V1\Wallet;

use App\Enums\WalletStatus;
use App\Http\Requests\Api\V1\ApiFormRequest;
use Illuminate\Validation\Rule;

class UpdateWalletRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'wallet_type_id' => ['sometimes', 'integer', 'exists:wallet_types,id'],
            'name' => ['sometimes', 'string', 'max:255'],
            'currency' => ['sometimes', 'string', 'size:3'],
            'status' => ['sometimes', Rule::enum(WalletStatus::class)],
            'is_default' => ['sometimes', 'boolean'],
        ];
    }
}
