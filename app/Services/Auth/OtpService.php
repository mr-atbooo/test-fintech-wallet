<?php

namespace App\Services\Auth;

use App\Enums\OtpPurpose;
use App\Exceptions\Auth\InvalidOtpException;
use App\Models\User;
use App\Repositories\OtpRepository;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class OtpService
{
    private const CODE_LENGTH = 6;

    private const TTL_MINUTES = 10;

    public function __construct(private readonly OtpRepository $otps) {}

    /**
     * Generate a new OTP for the user, invalidating any pending one
     * for the same purpose, and "deliver" it (logged for now — swap
     * for a real SMS/mail channel later).
     */
    public function generate(User $user, OtpPurpose $purpose): string
    {
        $this->otps->invalidatePending($user, $purpose);

        $code = str_pad((string) random_int(0, 999999), self::CODE_LENGTH, '0', STR_PAD_LEFT);

        $this->otps->create($user, $purpose, Hash::make($code), now()->addMinutes(self::TTL_MINUTES));

        Log::info("OTP for user #{$user->id} ({$purpose->value}): {$code}");

        return $code;
    }

    public function verify(User $user, OtpPurpose $purpose, string $code): void
    {
        $otp = $this->otps->findLatestPending($user, $purpose);

        if (! $otp || $otp->isExpired() || ! Hash::check($code, $otp->code)) {
            throw new InvalidOtpException();
        }

        $this->otps->markConsumed($otp);
    }
}
