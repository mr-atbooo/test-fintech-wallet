<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Actions\Auth\ForgotPasswordAction;
use App\Actions\Auth\LoginUserAction;
use App\Actions\Auth\LogoutUserAction;
use App\Actions\Auth\RefreshTokenAction;
use App\Actions\Auth\RegisterUserAction;
use App\Actions\Auth\VerifyOtpAction;
use App\Enums\OtpPurpose;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Auth\ForgotPasswordRequest;
use App\Http\Requests\Api\V1\Auth\LoginRequest;
use App\Http\Requests\Api\V1\Auth\LogoutRequest;
use App\Http\Requests\Api\V1\Auth\RefreshTokenRequest;
use App\Http\Requests\Api\V1\Auth\RegisterRequest;
use App\Http\Requests\Api\V1\Auth\VerifyOtpRequest;
use App\Http\Resources\AuthResource;
use App\Http\Resources\UserResource;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function register(RegisterRequest $request, RegisterUserAction $action): JsonResponse
    {
        $result = $action($request->validated());

        return ApiResponse::success(
            UserResource::make($result['user']),
            __('api.auth.registered'),
            $this->debugOtpMeta($result['otp_code']),
            201
        );
    }

    public function login(LoginRequest $request, LoginUserAction $action): JsonResponse
    {
        $result = $action($request->validated('identifier'), $request->validated('password'));

        return ApiResponse::success(AuthResource::make($result), __('api.auth.login_success'));
    }

    public function logout(LogoutRequest $request, LogoutUserAction $action): JsonResponse
    {
        $action($request->user(), $request->validated('refresh_token'));

        return ApiResponse::success(null, __('api.auth.logout_success'));
    }

    public function refresh(RefreshTokenRequest $request, RefreshTokenAction $action): JsonResponse
    {
        $result = $action($request->validated('refresh_token'));

        return ApiResponse::success(AuthResource::make($result), __('api.auth.token_refreshed'));
    }

    public function me(Request $request): JsonResponse
    {
        return ApiResponse::success(
            UserResource::make($request->user()->load('profile')),
            __('api.auth.me_fetched')
        );
    }

    public function forgotPassword(ForgotPasswordRequest $request, ForgotPasswordAction $action): JsonResponse
    {
        $result = $action($request->validated('identifier'));

        return ApiResponse::success(null, __('api.auth.otp_sent'), $this->debugOtpMeta($result['otp_code']));
    }

    public function verifyOtp(VerifyOtpRequest $request, VerifyOtpAction $action): JsonResponse
    {
        $user = $action(
            $request->validated('identifier'),
            $request->validated('code'),
            OtpPurpose::from($request->validated('purpose')),
            $request->validated('new_password'),
        );

        return ApiResponse::success(UserResource::make($user), __('api.auth.otp_verified'));
    }

    /**
     * Surface the OTP in the response outside production so it can be
     * consumed directly (Postman, automated tests, the Flutter app during
     * development) without needing a real SMS/mail provider wired up.
     */
    private function debugOtpMeta(string $otpCode): array
    {
        return app()->environment(['local', 'testing']) ? ['debug_otp_code' => $otpCode] : [];
    }
}
