<?php

namespace App\Enums;

enum NotificationType: string
{
    case Transaction = 'transaction';
    case LowBalance = 'low_balance';
    case PaymentSuccess = 'payment_success';
    case PaymentFailed = 'payment_failed';
}
