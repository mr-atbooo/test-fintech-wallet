<?php

namespace App\Repositories;

use App\Enums\OtpPurpose;
use App\Models\Otp;
use App\Models\User;
use DateTimeInterface;

class OtpRepository
{
    public function invalidatePending(User $user, OtpPurpose $purpose): void
    {
        Otp::where('user_id', $user->id)
            ->where('purpose', $purpose->value)
            ->whereNull('consumed_at')
            ->update(['consumed_at' => now()]);
    }

    public function create(User $user, OtpPurpose $purpose, string $hashedCode, DateTimeInterface $expiresAt): Otp
    {
        return Otp::create([
            'user_id' => $user->id,
            'purpose' => $purpose->value,
            'code' => $hashedCode,
            'expires_at' => $expiresAt,
        ]);
    }

    public function findLatestPending(User $user, OtpPurpose $purpose): ?Otp
    {
        return Otp::where('user_id', $user->id)
            ->where('purpose', $purpose->value)
            ->whereNull('consumed_at')
            ->latest('id')
            ->first();
    }

    public function markConsumed(Otp $otp): void
    {
        $otp->update(['consumed_at' => now()]);
    }
}
