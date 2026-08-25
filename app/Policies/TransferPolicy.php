<?php

namespace App\Policies;

use App\Models\Transfer;
use App\Models\User;

class TransferPolicy
{
    public function view(User $user, Transfer $transfer): bool
    {
        return $transfer->user_id === $user->id;
    }
}
