<?php

namespace Tests\Feature\Transaction;

use App\Models\Category;
use App\Models\WalletType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Concerns\CreatesAuthenticatedUser;
use Tests\TestCase;

class TransactionTest extends TestCase
{
    use CreatesAuthenticatedUser, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedReferenceData();
    }

    public function test_creating_income_transaction_increases_wallet_balance(): void
    {
        $session = $this->registerAndActivateUser();
        $wallet = $session['user']->wallets()->first();

        $response = $this->postJson('/api/v1/transactions', [
            'wallet_id' => $wallet->id,
            'type' => 'income',
            'amount' => 500,
        ], $this->authHeaders($session['token']));

        $response->assertCreated();
        $response->assertJsonPath('data.balance_before', 0);
        $response->assertJsonPath('data.balance_after', 500);

        $this->assertSame('500.00', $wallet->fresh()->balance);
    }

    public function test_creating_expense_transaction_decreases_wallet_balance(): void
    {
        $session = $this->registerAndActivateUser();
        $wallet = $session['user']->wallets()->first();

        $this->postJson('/api/v1/transactions', [
            'wallet_id' => $wallet->id,
            'type' => 'income',
            'amount' => 500,
        ], $this->authHeaders($session['token']))->assertCreated();

        $response = $this->postJson('/api/v1/transactions', [
            'wallet_id' => $wallet->id,
            'type' => 'expense',
            'amount' => 200,
        ], $this->authHeaders($session['token']));

        $response->assertCreated();
        $response->assertJsonPath('data.balance_before', 500);
        $response->assertJsonPath('data.balance_after', 300);

        $this->assertSame('300.00', $wallet->fresh()->balance);
    }

    public function test_expense_exceeding_balance_is_rejected_for_a_cash_wallet(): void
    {
        $session = $this->registerAndActivateUser();
        $wallet = $session['user']->wallets()->first();

        $response = $this->postJson('/api/v1/transactions', [
            'wallet_id' => $wallet->id,
            'type' => 'expense',
            'amount' => 100,
        ], $this->authHeaders($session['token']));

        $response->assertStatus(422);
        $this->assertSame('0.00', $wallet->fresh()->balance);
    }

    public function test_expense_is_allowed_to_go_negative_on_a_credit_wallet(): void
    {
        $session = $this->registerAndActivateUser();
        $creditType = WalletType::where('code', 'credit')->firstOrFail();

        $walletResponse = $this->postJson('/api/v1/wallets', [
            'wallet_type_id' => $creditType->id,
            'name' => 'My Credit Card',
        ], $this->authHeaders($session['token']));
        $walletResponse->assertCreated();
        $creditWalletId = $walletResponse->json('data.id');

        $response = $this->postJson('/api/v1/transactions', [
            'wallet_id' => $creditWalletId,
            'type' => 'expense',
            'amount' => 150,
        ], $this->authHeaders($session['token']));

        $response->assertCreated();
        $response->assertJsonPath('data.balance_after', -150);
    }

    public function test_category_type_mismatch_is_rejected(): void
    {
        $session = $this->registerAndActivateUser();
        $wallet = $session['user']->wallets()->first();
        $incomeCategory = Category::where('type', 'income')->firstOrFail();

        $response = $this->postJson('/api/v1/transactions', [
            'wallet_id' => $wallet->id,
            'category_id' => $incomeCategory->id,
            'type' => 'expense',
            'amount' => 50,
        ], $this->authHeaders($session['token']));

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('category_id');
    }

    public function test_updating_transaction_amount_recalculates_wallet_balance(): void
    {
        $session = $this->registerAndActivateUser();
        $wallet = $session['user']->wallets()->first();

        $this->postJson('/api/v1/transactions', [
            'wallet_id' => $wallet->id,
            'type' => 'income',
            'amount' => 1000,
        ], $this->authHeaders($session['token']))->assertCreated();

        $expenseResponse = $this->postJson('/api/v1/transactions', [
            'wallet_id' => $wallet->id,
            'type' => 'expense',
            'amount' => 300,
        ], $this->authHeaders($session['token']));
        $expenseResponse->assertCreated();
        $transactionId = $expenseResponse->json('data.id');

        $this->assertSame('700.00', $wallet->fresh()->balance);

        $updateResponse = $this->putJson("/api/v1/transactions/{$transactionId}", [
            'amount' => 100,
        ], $this->authHeaders($session['token']));

        $updateResponse->assertOk();
        $this->assertSame('900.00', $wallet->fresh()->balance);
    }

    public function test_deleting_transaction_reverses_wallet_balance(): void
    {
        $session = $this->registerAndActivateUser();
        $wallet = $session['user']->wallets()->first();

        $this->postJson('/api/v1/transactions', [
            'wallet_id' => $wallet->id,
            'type' => 'income',
            'amount' => 1000,
        ], $this->authHeaders($session['token']))->assertCreated();

        $expenseResponse = $this->postJson('/api/v1/transactions', [
            'wallet_id' => $wallet->id,
            'type' => 'expense',
            'amount' => 300,
        ], $this->authHeaders($session['token']));
        $transactionId = $expenseResponse->json('data.id');

        $this->assertSame('700.00', $wallet->fresh()->balance);

        $deleteResponse = $this->deleteJson("/api/v1/transactions/{$transactionId}", [], $this->authHeaders($session['token']));

        $deleteResponse->assertOk();
        $this->assertSame('1000.00', $wallet->fresh()->balance);
        $this->assertSoftDeleted('transactions', ['id' => $transactionId]);
    }

    public function test_user_cannot_view_another_users_transaction(): void
    {
        $userA = $this->registerAndActivateUser(['email' => 'owner@example.com']);
        $userB = $this->registerAndActivateUser(['email' => 'intruder@example.com']);

        $walletA = $userA['user']->wallets()->first();

        $transactionResponse = $this->postJson('/api/v1/transactions', [
            'wallet_id' => $walletA->id,
            'type' => 'income',
            'amount' => 100,
        ], $this->authHeaders($userA['token']));
        $transactionId = $transactionResponse->json('data.id');

        $response = $this->getJson("/api/v1/transactions/{$transactionId}", $this->authHeaders($userB['token']));

        $response->assertStatus(403);
    }
}
