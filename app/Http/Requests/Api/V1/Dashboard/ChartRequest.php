<?php

namespace App\Http\Requests\Api\V1\Dashboard;

use App\Http\Requests\Api\V1\ApiFormRequest;
use Illuminate\Validation\Rule;

class ChartRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'period' => ['nullable', Rule::in(['weekly', 'monthly'])],
        ];
    }
}
