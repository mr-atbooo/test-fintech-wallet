<?php

namespace App\Enums;

/**
 * Shared by transactions.type and categories.type — both describe
 * the same income/expense domain.
 */
enum TransactionType: string
{
    case Income = 'income';
    case Expense = 'expense';
}
