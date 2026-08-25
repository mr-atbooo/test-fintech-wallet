<?php

namespace Tests\Feature\Transfer;

use App\Models\Transaction;
use App\Models\WalletType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Concerns\CreatesAuthenticatedUser;
use Tests\TestCase;

class TransferTest extends TestCase
{
    use CreatesAuthenticatedUser, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedReferenceData();
    }

    private function createSecondWallet(string $token): int
    {
        $bankType = WalletType::where('code', 'bank')->firstOrFail();

        $response = $this->postJson('/api/v1/wallets', [
            'wallet_type_id' => $bankType->id,
            'name' => 'Bank Wallet',
        ], $this->authHeaders($token));

        $response->assertCreated();

        return $response->json('data.id');
    }

    public function test_transfer_between_own_wallets_updates_both_balances(): void
    {
        $session = $this->registerAndActivateUser();
        $cashWallet = $session['user']->wallets()->first();
        $bankWalletId = $this->createSecondWallet($session['token']);

        $this->postJson('/api/v1/transactions', [
            'wallet_id' => $cashWallet->id,
            'type' => 'income',
            'amount' => 1000,
        ], $this->authHeaders($session['token']))->assertCreated();

        $response = $this->postJson('/api/v1/transfers', [
            'from_wallet_id' => $cashWallet->id,
            'to_wallet_id' => $bankWalletId,
            'amount' => 400,
        ], $this->authHeaders($session['token']));

        $response->assertCreated();
        $response->assertJsonPath('data.from_wallet.balance', 600);
        $response->assertJsonPath('data.to_wallet.balance', 400);

        $this->assertSame('600.00', $cashWallet->fresh()->balance);
        $this->assertDatabaseHas('wallets', ['id' => $bankWalletId, 'balance' => '400.00']);
    }

    public function test_transfer_creates_mirror_transactions_linked_to_the_transfer(): void
    {
        $session = $this->registerAndActivateUser();
        $cashWallet = $session['user']->wallets()->first();
        $bankWalletId = $this->createSecondWallet($session['token']);

        $this->postJson('/api/v1/transactions', [
            'wallet_id' => $cashWallet->id,
            'type' => 'income',
            'amount' => 1000,
        ], $this->authHeaders($session['token']))->assertCreated();

        $transferResponse = $this->postJson('/api/v1/transfers', [
            'from_wallet_id' => $cashWallet->id,
            'to_wallet_id' => $bankWalletId,
            'amount' => 400,
        ], $this->authHeaders($session['token']));
        $transferId = $transferResponse->json('data.id');

        $this->assertDatabaseHas('transactions', [
            'transfer_id' => $transferId,
            'wallet_id' => $cashWallet->id,
            'type' => 'expense',
            'amount' => '400.00',
        ]);

        $this->assertDatabaseHas('transactions', [
            'transfer_id' => $transferId,
            'wallet_id' => $bankWalletId,
            'type' => 'income',
            'amount' => '400.00',
        ]);
    }

    public function test_transfer_fails_with_insufficient_balance(): void
    {
        $session = $this->registerAndActivateUser();
        $cashWallet = $session['user']->wallets()->first();
        $bankWalletId = $this->createSecondWallet($session['token']);

        $response = $this->postJson('/api/v1/transfers', [
            'from_wallet_id' => $cashWallet->id,
            'to_wallet_id' => $bankWalletId,
            'amount' => 50,
        ], $this->authHeaders($session['token']));

        $response->assertStatus(422);
        $this->assertSame('0.00', $cashWallet->fresh()->balance);
    }

    public function test_transfer_to_the_same_wallet_is_rejected(): void
    {
        $session = $this->registerAndActivateUser();
        $cashWallet = $session['user']->wallets()->first();

        $response = $this->postJson('/api/v1/transfers', [
            'from_wallet_id' => $cashWallet->id,
            'to_wallet_id' => $cashWallet->id,
            'amount' => 10,
        ], $this->authHeaders($session['token']));

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('to_wallet_id');
    }

    public function test_user_cannot_transfer_to_another_users_wallet(): void
    {
        $userA = $this->registerAndActivateUser(['email' => 'usera@example.com']);
        $userB = $this->registerAndActivateUser(['email' => 'userb@example.com']);

        $walletA = $userA['user']->wallets()->first();
        $walletB = $userB['user']->wallets()->first();

        $response = $this->postJson('/api/v1/transfers', [
            'from_wallet_id' => $walletA->id,
            'to_wallet_id' => $walletB->id,
            'amount' => 10,
        ], $this->authHeaders($userA['token']));

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('to_wallet_id');
    }

    public function test_transfer_linked_transaction_cannot_be_modified_or_deleted_directly(): void
    {
        $session = $this->registerAndActivateUser();
        $cashWallet = $session['user']->wallets()->first();
        $bankWalletId = $this->createSecondWallet($session['token']);

        $this->postJson('/api/v1/transactions', [
            'wallet_id' => $cashWallet->id,
            'type' => 'income',
            'amount' => 1000,
        ], $this->authHeaders($session['token']))->assertCreated();

        $transferResponse = $this->postJson('/api/v1/transfers', [
            'from_wallet_id' => $cashWallet->id,
            'to_wallet_id' => $bankWalletId,
            'amount' => 400,
        ], $this->authHeaders($session['token']));
        $transferId = $transferResponse->json('data.id');

        $mirrorTransactionId = Transaction::where('transfer_id', $transferId)
            ->where('wallet_id', $cashWallet->id)
            ->firstOrFail()
            ->id;

        $updateResponse = $this->putJson("/api/v1/transactions/{$mirrorTransactionId}", [
            'amount' => 999,
        ], $this->authHeaders($session['token']));
        $updateResponse->assertStatus(422);

        $deleteResponse = $this->deleteJson("/api/v1/transactions/{$mirrorTransactionId}", [], $this->authHeaders($session['token']));
        $deleteResponse->assertStatus(422);
    }
}
