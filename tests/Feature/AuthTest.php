<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\Concerns\BuildsFixtures;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase, BuildsFixtures;

    public function test_api_register_creates_customer_with_hashed_password_and_ignores_role_input(): void
    {
        $response = $this->postJson('/api/register', [
            'name' => 'New Customer', 'email' => 'new@example.test', 'password' => 'secret-pass-1', 'role' => 'admin',
        ]);

        $response->assertCreated()->assertJsonPath('user.role', 'customer')->assertJsonStructure(['token']);

        $user = User::where('email', 'new@example.test')->first();
        $this->assertSame('customer', $user->role);
        $this->assertTrue(Hash::check('secret-pass-1', $user->password));
        $this->assertNotSame('secret-pass-1', $user->password);
    }

    public function test_api_register_validates_input(): void
    {
        $this->postJson('/api/register', ['email' => 'not-an-email', 'password' => '123'])
            ->assertStatus(422)->assertJsonValidationErrors(['name', 'email', 'password']);
    }

    public function test_api_login_with_valid_credentials_returns_token(): void
    {
        $user = $this->makeUser(User::ROLE_CUSTOMER, 'login@example.test');

        $this->postJson('/api/login', ['email' => 'login@example.test', 'password' => 'password'])
            ->assertOk()->assertJsonPath('user.id', $user->id)->assertJsonStructure(['token']);
    }

    public function test_api_login_with_invalid_credentials_returns_401(): void
    {
        $this->makeUser(User::ROLE_CUSTOMER, 'login@example.test');

        $this->postJson('/api/login', ['email' => 'login@example.test', 'password' => 'wrong-password'])
            ->assertStatus(401)->assertJsonPath('message', 'Invalid credentials.');
    }

    public function test_api_requires_authentication(): void
    {
        $this->getJson('/api/user')->assertStatus(401);
        $this->getJson('/api/shipments')->assertStatus(401);
    }

    public function test_api_token_grants_access_and_logout_revokes_tokens(): void
    {
        $user = $this->makeUser(User::ROLE_OPERATOR, 'op@example.test');

        $token = $this->postJson('/api/login', ['email' => 'op@example.test', 'password' => 'password'])->json('token');

        $this->withHeader('Authorization', 'Bearer '.$token)->getJson('/api/user')
            ->assertOk()->assertJsonPath('user.role', 'operator');

        $this->withHeader('Authorization', 'Bearer '.$token)->postJson('/api/logout')->assertOk();
        $this->assertSame(0, $user->tokens()->count());
    }

    public function test_web_guest_is_redirected_to_login(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
        $this->get('/shipments')->assertRedirect('/login');
    }

    public function test_web_login_with_wrong_password_fails_and_correct_password_works(): void
    {
        $this->makeUser(User::ROLE_CUSTOMER, 'web@example.test');

        $this->post('/login', ['email' => 'web@example.test', 'password' => 'nope'])
            ->assertSessionHasErrors('email');
        $this->assertGuest();

        $this->post('/login', ['email' => 'web@example.test', 'password' => 'password'])
            ->assertRedirect('/dashboard');
        $this->assertAuthenticated();
    }
}
