<?php

namespace Tests\Feature\Wallet;

use App\Models\WalletType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Concerns\CreatesAuthenticatedUser;
use Tests\TestCase;

class WalletTest extends TestCase
{
    use CreatesAuthenticatedUser, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedReferenceData();
    }

    public function test_registration_creates_a_default_cash_wallet(): void
    {
        $session = $this->registerAndActivateUser();

        $response = $this->getJson('/api/v1/wallets', $this->authHeaders($session['token']));

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.is_default', true);
    }

    public function test_setting_a_new_wallet_as_default_unsets_the_previous_one(): void
    {
        $session = $this->registerAndActivateUser();
        $bankType = WalletType::where('code', 'bank')->firstOrFail();

        $newWalletResponse = $this->postJson('/api/v1/wallets', [
            'wallet_type_id' => $bankType->id,
            'name' => 'Bank Wallet',
            'is_default' => true,
        ], $this->authHeaders($session['token']));
        $newWalletResponse->assertCreated();
        $newWalletId = $newWalletResponse->json('data.id');

        $walletsResponse = $this->getJson('/api/v1/wallets', $this->authHeaders($session['token']));
        $wallets = collect($walletsResponse->json('data'));

        $this->assertTrue($wallets->firstWhere('id', $newWalletId)['is_default']);
        $this->assertSame(1, $wallets->where('is_default', true)->count());
    }

    public function test_wallet_with_non_zero_balance_cannot_be_deleted(): void
    {
        $session = $this->registerAndActivateUser();
        $wallet = $session['user']->wallets()->first();

        $this->postJson('/api/v1/transactions', [
            'wallet_id' => $wallet->id,
            'type' => 'income',
            'amount' => 100,
        ], $this->authHeaders($session['token']))->assertCreated();

        $response = $this->deleteJson("/api/v1/wallets/{$wallet->id}", [], $this->authHeaders($session['token']));

        $response->assertStatus(422);
        $this->assertDatabaseHas('wallets', ['id' => $wallet->id, 'deleted_at' => null]);
    }

    public function test_user_cannot_update_another_users_wallet(): void
    {
        $userA = $this->registerAndActivateUser(['email' => 'walleta@example.com']);
        $userB = $this->registerAndActivateUser(['email' => 'walletb@example.com']);

        $walletA = $userA['user']->wallets()->first();

        $response = $this->putJson("/api/v1/wallets/{$walletA->id}", [
            'name' => 'Hacked Name',
        ], $this->authHeaders($userB['token']));

        $response->assertStatus(403);
        $this->assertDatabaseMissing('wallets', ['id' => $walletA->id, 'name' => 'Hacked Name']);
    }
}
