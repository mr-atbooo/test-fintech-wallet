<?php

namespace App\Exceptions\Auth;

use App\Exceptions\Api\ApiException;

class UserNotFoundException extends ApiException
{
    public function __construct()
    {
        parent::__construct(__('api.auth.user_not_found'), 404);
    }
}
