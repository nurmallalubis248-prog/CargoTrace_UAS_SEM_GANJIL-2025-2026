<?php

namespace Tests\Feature;

use App\Enums\ExceptionType;
use Tests\TestCase;

/**
 * The UI language is switched in the browser (public/js/ct-i18n.js + ct-dict.js, stored in localStorage).
 * The server-side lang files are used for the PDF report, so they must stay complete in both languages.
 */
class LocaleTest extends TestCase
{
    public function test_login_page_ships_english_source_text_and_the_client_side_switcher(): void
    {
        $this->get('/login')->assertOk()
            ->assertSee('Sign in to your account')
            ->assertSee('data-lang-select', false)
            ->assertSee('ct-i18n.js', false)
            ->assertSee('ct-dict.js', false);
    }

    public function test_pdf_translation_groups_have_the_same_keys_in_english_and_indonesian(): void
    {
        foreach (['exception_types', 'reports'] as $group) {
            $en = array_keys(trans('ui.'.$group, [], 'en'));
            $id = array_keys(trans('ui.'.$group, [], 'id'));
            sort($en);
            sort($id);

            $this->assertNotEmpty($en, $group);
            $this->assertSame($en, $id, "ui.{$group} differs between en and id");
        }
    }

    public function test_every_delay_reason_has_an_indonesian_text(): void
    {
        foreach (ExceptionType::cases() as $type) {
            $key = 'ui.exception_types.'.$type->value;
            $this->assertNotSame($key, __($key, [], 'id'), "{$type->value} is not translated to Indonesian");
        }
    }
}
