<?php

namespace App\Http\Requests\Api\V1\Transaction;

use App\Enums\TransactionType;
use App\Http\Requests\Api\V1\ApiFormRequest;
use Illuminate\Validation\Rule;

class IndexTransactionRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type' => ['nullable', Rule::enum(TransactionType::class)],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'wallet_id' => ['nullable', 'integer', 'exists:wallets,id'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'search' => ['nullable', 'string', 'max:255'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
