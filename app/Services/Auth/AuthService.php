<?php

namespace App\Services\Auth;

use App\Enums\OtpPurpose;
use App\Enums\UserStatus;
use App\Enums\WalletTypeCode;
use App\Exceptions\Auth\AccountNotActiveException;
use App\Exceptions\Auth\InvalidCredentialsException;
use App\Exceptions\Auth\UserNotFoundException;
use App\Models\User;
use App\Repositories\UserRepository;
use App\Repositories\WalletRepository;
use App\Repositories\WalletTypeRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AuthService
{
    public function __construct(
        private readonly UserRepository $users,
        private readonly WalletRepository $wallets,
        private readonly WalletTypeRepository $walletTypes,
        private readonly OtpService $otps,
    ) {}

    /**
     * @return array{user: User, otp_code: string}
     */
    public function register(array $data): array
    {
        return DB::transaction(function () use ($data) {
            $user = $this->users->create([
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'] ?? null,
                'password' => $data['password'],
                'status' => UserStatus::Pending,
            ]);

            $user->profile()->create([
                'first_name' => $data['first_name'] ?? null,
                'last_name' => $data['last_name'] ?? null,
            ]);

            $cashWalletType = $this->walletTypes->findByCode(WalletTypeCode::Cash);

            if ($cashWalletType) {
                $this->wallets->createDefault($user, $cashWalletType, $data['currency'] ?? 'USD');
            }

            $otpCode = $this->otps->generate($user, OtpPurpose::Registration);

            return ['user' => $user, 'otp_code' => $otpCode];
        });
    }

    public function findUserForLogin(string $identifier, string $password): User
    {
        $user = $this->users->findByEmailOrPhone($identifier);

        if (! $user || ! Hash::check($password, $user->password)) {
            throw new InvalidCredentialsException();
        }

        if ($user->status !== UserStatus::Active) {
            throw new AccountNotActiveException($user->status);
        }

        return $user;
    }

    /**
     * @return array{user: User, otp_code: string}
     */
    public function requestPasswordResetOtp(string $identifier): array
    {
        $user = $this->users->findByEmailOrPhone($identifier);

        if (! $user) {
            throw new UserNotFoundException();
        }

        $otpCode = $this->otps->generate($user, OtpPurpose::PasswordReset);

        return ['user' => $user, 'otp_code' => $otpCode];
    }

    public function verifyRegistrationOtp(string $identifier, string $code): User
    {
        $user = $this->users->findByEmailOrPhone($identifier);

        if (! $user) {
            throw new UserNotFoundException();
        }

        $this->otps->verify($user, OtpPurpose::Registration, $code);

        $user->status = UserStatus::Active;

        if ($user->email === $identifier) {
            $user->email_verified_at = now();
        }

        if ($user->phone === $identifier) {
            $user->phone_verified_at = now();
        }

        $user->save();

        return $user;
    }

    public function verifyPasswordResetOtp(string $identifier, string $code, string $newPassword): User
    {
        $user = $this->users->findByEmailOrPhone($identifier);

        if (! $user) {
            throw new UserNotFoundException();
        }

        $this->otps->verify($user, OtpPurpose::PasswordReset, $code);

        $user->password = $newPassword;
        $user->save();

        return $user;
    }
}
