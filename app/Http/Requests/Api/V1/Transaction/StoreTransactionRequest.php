<?php

namespace App\Http\Requests\Api\V1\Transaction;

use App\Enums\TransactionType;
use App\Http\Requests\Api\V1\ApiFormRequest;
use App\Models\Category;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\Rule;

class StoreTransactionRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'wallet_id' => [
                'required',
                'integer',
                Rule::exists('wallets', 'id')->where(fn ($query) => $query->where('user_id', $this->user()->id)),
            ],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'type' => ['required', Rule::enum(TransactionType::class)],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'note' => ['nullable', 'string', 'max:255'],
            'transaction_date' => ['nullable', 'date'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $categoryId = $this->input('category_id');
            $type = $this->input('type');

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
