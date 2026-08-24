<?php

namespace App\Enums;

/**
 * Generic active/inactive status for simple lookup-style tables
 * (wallet_types, categories) that don't need their own status enum.
 */
enum Status: string
{
    case Active = 'active';
    case Inactive = 'inactive';
}
