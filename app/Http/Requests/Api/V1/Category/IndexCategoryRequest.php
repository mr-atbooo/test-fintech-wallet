<?php

namespace App\Http\Requests\Api\V1\Category;

use App\Enums\TransactionType;
use App\Http\Requests\Api\V1\ApiFormRequest;
use Illuminate\Validation\Rule;

class IndexCategoryRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type' => ['nullable', Rule::enum(TransactionType::class)],
        ];
    }
}
