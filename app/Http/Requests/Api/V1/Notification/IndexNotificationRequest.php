<?php

namespace App\Http\Requests\Api\V1\Notification;

use App\Http\Requests\Api\V1\ApiFormRequest;

class IndexNotificationRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'unread_only' => ['nullable', 'boolean'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
