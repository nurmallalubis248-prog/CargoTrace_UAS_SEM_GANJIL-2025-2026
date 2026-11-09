<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Tests\Concerns\BuildsFixtures;
use Tests\TestCase;

class EmailVerificationTest extends TestCase
{
    use RefreshDatabase, BuildsFixtures;

    private function unverifiedCustomer(): User
    {
        $user = $this->makeUser(User::ROLE_CUSTOMER);
        $user->forceFill(['email_verified_at' => null])->save();

        return $user->fresh();
    }

    public function test_registration_sends_a_verification_email_and_asks_to_verify(): void
    {
        Notification::fake();

        $this->post('/register', [
            'name' => 'New Customer', 'email' => 'new@example.test',
            'password' => 'secret-pass-1', 'password_confirmation' => 'secret-pass-1',
        ])->assertRedirect(route('verification.notice'));

        $user = User::where('email', 'new@example.test')->firstOrFail();
        $this->assertNull($user->email_verified_at);
        $this->assertSame('customer', $user->role);
        Notification::assertSentTo($user, VerifyEmail::class);
    }

    public function test_unverified_user_cannot_open_the_app_until_verified(): void
    {
        $user = $this->unverifiedCustomer();

        $this->actingAs($user)->get('/dashboard')->assertRedirect(route('verification.notice'));
        $this->actingAs($user)->get('/shipments')->assertRedirect(route('verification.notice'));
        $this->actingAs($user)->get(route('verification.notice'))->assertOk();
    }

    public function test_signed_link_verifies_the_email(): void
    {
        $user = $this->unverifiedCustomer();
        $url = URL::temporarySignedRoute('verification.verify', now()->addMinutes(60), [
            'id' => $user->id, 'hash' => sha1($user->getEmailForVerification()),
        ]);

        $this->actingAs($user)->get($url)->assertRedirect(route('dashboard'));

        $this->assertNotNull($user->fresh()->email_verified_at);
        $this->actingAs($user)->get('/dashboard')->assertOk();
    }

    public function test_link_with_wrong_hash_does_not_verify(): void
    {
        $user = $this->unverifiedCustomer();
        $url = URL::temporarySignedRoute('verification.verify', now()->addMinutes(60), [
            'id' => $user->id, 'hash' => sha1('someone-else@example.test'),
        ]);

        $this->actingAs($user)->get($url)->assertForbidden();

        $this->assertNull($user->fresh()->email_verified_at);
    }

    public function test_verification_email_can_be_resent(): void
    {
        Notification::fake();
        $user = $this->unverifiedCustomer();

        $this->actingAs($user)->post(route('verification.send'))->assertSessionHas('status', 'verification-link-sent');

        Notification::assertSentTo($user, VerifyEmail::class);
    }

    public function test_users_created_by_an_admin_are_already_verified(): void
    {
        $admin = $this->makeUser(User::ROLE_ADMIN);

        $this->actingAs($admin)->post('/admin/users', [
            'name' => 'Staff', 'email' => 'staff@example.test', 'password' => 'secret-pass-1', 'role' => 'operator',
        ])->assertSessionHas('success');

        $this->assertNotNull(User::where('email', 'staff@example.test')->first()->email_verified_at);
    }
}
