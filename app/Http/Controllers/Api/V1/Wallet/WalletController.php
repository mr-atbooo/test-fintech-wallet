<?php

namespace App\Http\Controllers\Api\V1\Wallet;

use App\Actions\Wallet\CreateWalletAction;
use App\Actions\Wallet\DeleteWalletAction;
use App\Actions\Wallet\ListWalletsAction;
use App\Actions\Wallet\UpdateWalletAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Wallet\StoreWalletRequest;
use App\Http\Requests\Api\V1\Wallet\UpdateWalletRequest;
use App\Http\Resources\WalletResource;
use App\Models\Wallet;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WalletController extends Controller
{
    public function index(Request $request, ListWalletsAction $action): JsonResponse
    {
        $wallets = $action($request->user());

        return ApiResponse::success(WalletResource::collection($wallets), __('api.wallet.fetched'));
    }

    public function store(StoreWalletRequest $request, CreateWalletAction $action): JsonResponse
    {
        $wallet = $action($request->user(), $request->validated());

        return ApiResponse::success(WalletResource::make($wallet->load('walletType')), __('api.wallet.created'), [], 201);
    }

    public function show(Request $request, Wallet $wallet): JsonResponse
    {
        $this->authorize('view', $wallet);

        return ApiResponse::success(WalletResource::make($wallet->load('walletType')), __('api.wallet.fetched'));
    }

    public function update(UpdateWalletRequest $request, Wallet $wallet, UpdateWalletAction $action): JsonResponse
    {
        $this->authorize('update', $wallet);

        $wallet = $action($wallet, $request->validated());

        return ApiResponse::success(WalletResource::make($wallet->load('walletType')), __('api.wallet.updated'));
    }

    public function destroy(Request $request, Wallet $wallet, DeleteWalletAction $action): JsonResponse
    {
        $this->authorize('delete', $wallet);

        $action($wallet);

        return ApiResponse::success(null, __('api.wallet.deleted'));
    }
}
