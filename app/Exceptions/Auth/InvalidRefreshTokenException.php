<?php

namespace App\Exceptions\Auth;

use App\Exceptions\Api\ApiException;

class InvalidRefreshTokenException extends ApiException
{
    public function __construct()
    {
        parent::__construct(__('api.auth.invalid_refresh_token'), 401);
    }
}
