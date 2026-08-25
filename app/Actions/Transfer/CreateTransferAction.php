<?php

namespace App\Actions\Transfer;

use App\Models\Transfer;
use App\Models\User;
use App\Services\Transfer\TransferService;

class CreateTransferAction
{
    public function __construct(private readonly TransferService $transferService) {}

    public function __invoke(User $user, array $data): Transfer
    {
        return $this->transferService->create($user, $data);
    }
}
