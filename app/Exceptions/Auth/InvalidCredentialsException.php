<?php

namespace App\Exceptions\Auth;

use App\Exceptions\Api\ApiException;

class InvalidCredentialsException extends ApiException
{
    public function __construct()
    {
        parent::__construct(__('api.auth.invalid_credentials'), 401);
    }
}
