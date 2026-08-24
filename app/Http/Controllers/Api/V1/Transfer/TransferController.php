<?php

namespace App\Http\Controllers\Api\V1\Transfer;

use App\Actions\Transfer\CreateTransferAction;
use App\Actions\Transfer\ListTransfersAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Transfer\IndexTransferRequest;
use App\Http\Requests\Api\V1\Transfer\StoreTransferRequest;
use App\Http\Resources\TransferResource;
use App\Models\Transfer;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TransferController extends Controller
{
    public function index(IndexTransferRequest $request, ListTransfersAction $action): JsonResponse
    {
        $transfers = $action($request->user(), $request->validated());

        return ApiResponse::success(TransferResource::collection($transfers), __('api.transfer.fetched'));
    }

    public function store(StoreTransferRequest $request, CreateTransferAction $action): JsonResponse
    {
        $transfer = $action($request->user(), $request->validated());

        return ApiResponse::success(TransferResource::make($transfer), __('api.transfer.created'), [], 201);
    }

    public function show(Request $request, Transfer $transfer): JsonResponse
    {
        $this->authorize('view', $transfer);

        return ApiResponse::success(
            TransferResource::make($transfer->load(['fromWallet.walletType', 'toWallet.walletType'])),
            __('api.transfer.fetched')
        );
    }
}
