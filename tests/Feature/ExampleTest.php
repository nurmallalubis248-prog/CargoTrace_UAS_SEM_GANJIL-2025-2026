<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsFixtures;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase, BuildsFixtures;

    public function test_guests_are_sent_to_the_login_page(): void
    {
        $this->get('/')->assertRedirect('/login');
    }

    public function test_logged_in_users_are_sent_to_the_dashboard(): void
    {
        $this->actingAs($this->makeUser(User::ROLE_CUSTOMER))->get('/')->assertRedirect('/dashboard');
    }
}
