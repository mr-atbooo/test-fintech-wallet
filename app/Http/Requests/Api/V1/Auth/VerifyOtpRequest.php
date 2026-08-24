<?php

namespace App\Http\Requests\Api\V1\Auth;

use App\Enums\OtpPurpose;
use App\Http\Requests\Api\V1\ApiFormRequest;
use Illuminate\Validation\Rule;

class VerifyOtpRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'identifier' => ['required', 'string'],
            'code' => ['required', 'digits:6'],
            'purpose' => ['required', Rule::enum(OtpPurpose::class)],
            'new_password' => ['required_if:purpose,'.OtpPurpose::PasswordReset->value, 'string', 'min:8', 'confirmed'],
        ];
    }
}
