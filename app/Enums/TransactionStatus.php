<?php

namespace App\Enums;

/**
 * Shared by transactions.status and transfers.status.
 */
enum TransactionStatus: string
{
    case Completed = 'completed';
    case Pending = 'pending';
    case Failed = 'failed';
}
