<?php

namespace App\Http\Controllers\Api\V1\Transaction;

use App\Actions\Transaction\CreateTransactionAction;
use App\Actions\Transaction\DeleteTransactionAction;
use App\Actions\Transaction\ListTransactionsAction;
use App\Actions\Transaction\UpdateTransactionAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Transaction\IndexTransactionRequest;
use App\Http\Requests\Api\V1\Transaction\StoreTransactionRequest;
use App\Http\Requests\Api\V1\Transaction\UpdateTransactionRequest;
use App\Http\Resources\TransactionResource;
use App\Models\Transaction;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    public function index(IndexTransactionRequest $request, ListTransactionsAction $action): JsonResponse
    {
        $transactions = $action($request->user(), $request->validated());

        return ApiResponse::success(TransactionResource::collection($transactions), __('api.transaction.fetched'));
    }

    public function store(StoreTransactionRequest $request, CreateTransactionAction $action): JsonResponse
    {
        $transaction = $action($request->user(), $request->validated());

        return ApiResponse::success(
            TransactionResource::make($transaction->load(['wallet.walletType', 'category'])),
            __('api.transaction.created'),
            [],
            201
        );
    }

    public function show(Request $request, Transaction $transaction): JsonResponse
    {
        $this->authorize('view', $transaction);

        return ApiResponse::success(
            TransactionResource::make($transaction->load(['wallet.walletType', 'category'])),
            __('api.transaction.fetched')
        );
    }

    public function update(UpdateTransactionRequest $request, Transaction $transaction, UpdateTransactionAction $action): JsonResponse
    {
        $this->authorize('update', $transaction);

        $transaction = $action($transaction, $request->validated());

        return ApiResponse::success(TransactionResource::make($transaction), __('api.transaction.updated'));
    }

    public function destroy(Request $request, Transaction $transaction, DeleteTransactionAction $action): JsonResponse
    {
        $this->authorize('delete', $transaction);

        $action($transaction);

        return ApiResponse::success(null, __('api.transaction.deleted'));
    }
}
