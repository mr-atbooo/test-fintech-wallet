<?php

namespace App\Http\Requests\Api\V1\Dashboard;

use App\Http\Requests\Api\V1\ApiFormRequest;

class SummaryRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
        ];
    }
}
