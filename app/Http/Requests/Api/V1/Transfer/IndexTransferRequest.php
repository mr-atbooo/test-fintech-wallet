<?php

namespace App\Http\Requests\Api\V1\Transfer;

use App\Http\Requests\Api\V1\ApiFormRequest;

class IndexTransferRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
