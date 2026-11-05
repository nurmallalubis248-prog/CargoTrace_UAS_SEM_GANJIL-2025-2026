<?php

namespace Tests\Feature;

use Tests\TestCase;

class LocaleTest extends TestCase
{
    public function test_default_language_is_english(): void
    {
        $this->get('/login')->assertOk()->assertSee('Welcome back');
    }

    public function test_switching_language_stores_session_and_cookie(): void
    {
        $this->get('/lang/id')
            ->assertRedirect()
            ->assertSessionHas('locale', 'id')
            ->assertCookie('locale', 'id');
    }

    public function test_pages_render_in_the_selected_language(): void
    {
        $this->withSession(['locale' => 'id'])->get('/login')
            ->assertOk()
            ->assertSee('Selamat datang kembali');
    }

    public function test_unsupported_language_is_rejected(): void
    {
        $this->get('/lang/xx')->assertNotFound();
    }
}
