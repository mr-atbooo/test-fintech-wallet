<?php

namespace App\Exceptions\Auth;

use App\Enums\UserStatus;
use App\Exceptions\Api\ApiException;

class AccountNotActiveException extends ApiException
{
    public function __construct(UserStatus $status)
    {
        $messageKey = match ($status) {
            UserStatus::Pending => 'api.auth.account_pending',
            UserStatus::Blocked => 'api.auth.account_blocked',
            default => 'api.auth.account_inactive',
        };

        parent::__construct(__($messageKey), 403);
    }
}
