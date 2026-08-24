<?php

namespace App\Http\Requests\Api\V1\Transaction;

use App\Enums\TransactionType;
use App\Http\Requests\Api\V1\ApiFormRequest;
use App\Models\Category;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\Rule;

class UpdateTransactionRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'wallet_id' => [
                'sometimes',
                'integer',
                Rule::exists('wallets', 'id')->where(fn ($query) => $query->where('user_id', $this->user()->id)),
            ],
            'category_id' => ['sometimes', 'nullable', 'integer', 'exists:categories,id'],
            'type' => ['sometimes', Rule::enum(TransactionType::class)],
            'amount' => ['sometimes', 'numeric', 'min:0.01'],
            'note' => ['sometimes', 'nullable', 'string', 'max:255'],
            'transaction_date' => ['sometimes', 'date'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $transaction = $this->route('transaction');

            $categoryId = $this->has('category_id') ? $this->input('category_id') : $transaction?->category_id;
            $type = $this->input('type', $transaction?->type?->value);

            if (! $categoryId || ! $type) {
                return;
            }

            $category = Category::find($categoryId);

            if ($category && $category->type->value !== $type) {
                $validator->errors()->add('category_id', __('api.transaction.category_type_mismatch'));
            }
        });
    }
}
