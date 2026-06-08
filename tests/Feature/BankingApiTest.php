<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class BankingApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_register_creates_user_account_and_token()
    {
        $response = $this->postJson('/api/register', $this->userPayload());

        $response->assertCreated()
            ->assertJsonStructure([
                'message',
                'data' => [
                    'id',
                    'first_name',
                    'last_name',
                    'email',
                    'accounts' => [
                        '*' => [
                            'id',
                            'account_number',
                            'account_type',
                            'balance',
                        ],
                    ],
                ],
                'token',
            ]);

        $this->assertDatabaseHas('users', [
            'email' => 'ana@example.com',
        ]);

        $this->assertDatabaseHas('accounts', [
            'account_type' => 'savings',
            'balance' => '0.00',
        ]);
    }

    public function test_api_validation_errors_return_json_without_accept_header()
    {
        $payload = $this->userPayload();
        $payload['password'] = '1234';

        $this->post('/api/register', $payload)
            ->assertUnprocessable()
            ->assertHeader('content-type', 'application/json')
            ->assertJsonValidationErrors('password');
    }

    public function test_protected_routes_require_authentication()
    {
        $this->getJson('/api/accounts')->assertUnauthorized();
    }

    public function test_users_endpoint_requires_admin_role()
    {
        $user = $this->registerUser('ana@example.com', 'savings');

        $this->withToken($user['token'])
            ->getJson('/api/users')
            ->assertForbidden()
            ->assertJsonPath('message', 'Admin access is required.');
    }

    public function test_admin_can_list_and_delete_customer_users()
    {
        $admin = $this->createUserWithToken('admin@example.com', 'admin');
        $customer = $this->createUserWithToken('customer@example.com', 'customer');

        $this->withToken($admin['token'])
            ->getJson('/api/users')
            ->assertOk()
            ->assertJsonFragment([
                'email' => 'admin@example.com',
                'role' => 'admin',
            ])
            ->assertJsonFragment([
                'email' => 'customer@example.com',
                'role' => 'customer',
            ]);

        $this->withToken($admin['token'])
            ->deleteJson('/api/user/'.$customer['user']->id)
            ->assertOk();

        $this->assertDatabaseMissing('users', [
            'id' => $customer['user']->id,
        ]);
    }

    public function test_authenticated_user_can_update_profile()
    {
        $user = $this->registerUser('ana@example.com', 'savings');

        $this->withToken($user['token'])
            ->patchJson('/api/me', [
                'first_name' => 'Andrea',
                'last_name' => 'Lopez',
                'phone' => '04147654321',
            ])
            ->assertOk()
            ->assertJsonPath('data.first_name', 'Andrea')
            ->assertJsonPath('data.last_name', 'Lopez')
            ->assertJsonPath('data.phone', '04147654321');

        $this->assertDatabaseHas('users', [
            'email' => 'ana@example.com',
            'first_name' => 'Andrea',
            'last_name' => 'Lopez',
            'phone' => '04147654321',
        ]);
    }

    public function test_authenticated_user_can_deposit_withdraw_and_transfer()
    {
        $firstUser = $this->registerUser('ana@example.com', 'savings');
        $secondUser = $this->registerUser('luis@example.com', 'checking');

        $firstAccountNumber = $firstUser['account']['account_number'];
        $secondAccountNumber = $secondUser['account']['account_number'];

        $this->withToken($firstUser['token'])
            ->putJson('/api/deposit', [
                'account_number' => $firstAccountNumber,
                'account_type' => 'savings',
                'amount' => '100.00',
            ])
            ->assertOk()
            ->assertJsonPath('data.current_balance', '100.00');

        $this->withToken($firstUser['token'])
            ->putJson('/api/withdrawal', [
                'account_number' => $firstAccountNumber,
                'account_type' => 'savings',
                'amount' => '30.25',
            ])
            ->assertOk()
            ->assertJsonPath('data.current_balance', '69.75');

        $this->withToken($firstUser['token'])
            ->postJson('/api/transaction', [
                'source_account_number' => $firstAccountNumber,
                'destination_account_number' => $secondAccountNumber,
                'amount' => '20.75',
                'transaction_type' => 'transfer',
            ])
            ->assertOk()
            ->assertJsonPath('data.current_balance', '49.00');

        $this->assertSame('49.00', Account::where('account_number', $firstAccountNumber)->value('balance'));
        $this->assertSame('20.75', Account::where('account_number', $secondAccountNumber)->value('balance'));
    }

    public function test_user_cannot_operate_another_users_account()
    {
        $firstUser = $this->registerUser('ana@example.com', 'savings');
        $secondUser = $this->registerUser('luis@example.com', 'checking');

        $this->withToken($firstUser['token'])
            ->putJson('/api/deposit', [
                'account_number' => $secondUser['account']['account_number'],
                'amount' => '10.00',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('account_number');
    }

    public function test_withdrawal_rejects_insufficient_balance()
    {
        $user = $this->registerUser('ana@example.com', 'savings');

        $this->withToken($user['token'])
            ->putJson('/api/withdrawal', [
                'account_number' => $user['account']['account_number'],
                'amount' => '1.00',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('amount');
    }

    private function registerUser(string $email, string $accountType)
    {
        $payload = $this->userPayload($email, $accountType);
        $response = $this->postJson('/api/register', $payload)->assertCreated();

        return [
            'token' => $response->json('token'),
            'account' => $response->json('data.accounts.0'),
        ];
    }

    private function createUserWithToken(string $email, string $role)
    {
        $user = User::create([
            'first_name' => ucfirst($role),
            'last_name' => 'User',
            'phone' => '04140000000',
            'email' => $email,
            'password' => Hash::make('password123'),
            'role' => $role,
        ]);

        return [
            'user' => $user,
            'token' => $user->createToken('API TOKEN')->plainTextToken,
        ];
    }

    private function userPayload(string $email = 'ana@example.com', string $accountType = 'savings')
    {
        return [
            'first_name' => 'Ana',
            'last_name' => 'Perez',
            'phone' => '04141234567',
            'email' => $email,
            'password' => 'password123',
            'account_type' => $accountType,
        ];
    }
}
