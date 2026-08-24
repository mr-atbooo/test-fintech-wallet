<?php

namespace App\Actions\Transfer;

use App\Models\User;
use App\Services\Transfer\TransferService;
use Illuminate\Pagination\LengthAwarePaginator;

class ListTransfersAction
{
    public function __construct(private readonly TransferService $transferService) {}

    public function __invoke(User $user, array $filters): LengthAwarePaginator
    {
        return $this->transferService->list($user, $filters);
    }
}
