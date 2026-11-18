<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Database\Factories\InquiryFactory;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class AdminAuthTest extends TestCase
{
    use RefreshDatabase;

    private const WRONG = 'E-mail nebo heslo nesouhlasí. Zkontrolujte je a zkuste to znovu.';

    private function admin(): User
    {
        return User::factory()->create(['name' => 'Jana Správcová', 'email' => 'jana@example.test', 'password' => 'spravne-heslo']);
    }

    public function test_guest_is_redirected_to_login_everywhere(): void
    {
        $inquiry = InquiryFactory::new()->create();

        $this->get('/admin')->assertRedirect('/admin/poptavky');
        $this->get('/admin/poptavky')->assertRedirect(route('admin.login'));
        $this->get(route('admin.inquiries.show', $inquiry))->assertRedirect(route('admin.login'));
        $this->patch(route('admin.inquiries.status', $inquiry), ['status' => 'confirmed'])->assertRedirect(route('admin.login'));
        $this->patch(route('admin.inquiries.note', $inquiry), ['internal_note' => 'x'])->assertRedirect(route('admin.login'));
        $this->post(route('admin.inquiries.resend', $inquiry))->assertRedirect(route('admin.login'));

        $this->assertSame('new', $inquiry->fresh()->status);
        $this->assertNull($inquiry->fresh()->internal_note);
    }

    public function test_login_page_is_czech_and_not_indexed(): void
    {
        $this->get('/admin/login')
            ->assertOk()
            ->assertSee('Přihlášení')
            ->assertSee('Zapamatovat si mě')
            ->assertSee('<meta name="robots" content="noindex, nofollow">', false)
            ->assertSee('name="_token"', false)
            ->assertHeader('X-Robots-Tag', 'noindex, nofollow');
    }

    public function test_empty_form_gets_czech_messages(): void
    {
        $this->from('/admin/login')
            ->post('/admin/login', ['email' => '', 'password' => ''])
            ->assertRedirect('/admin/login')
            ->assertSessionHasErrors(['email' => 'Vyplňte e-mail.', 'password' => 'Vyplňte heslo.']);

        $this->from('/admin/login')
            ->post('/admin/login', ['email' => 'neni-email', 'password' => 'x'])
            ->assertSessionHasErrors(['email' => 'Zadejte e-mail ve tvaru jmeno@domena.cz.']);
    }

    public function test_wrong_password_fails(): void
    {
        $this->admin();

        $this->from('/admin/login')
            ->post('/admin/login', ['email' => 'jana@example.test', 'password' => 'spatne-heslo'])
            ->assertRedirect('/admin/login')
            ->assertSessionHasErrors(['email' => self::WRONG])
            ->assertSessionHasInput('email', 'jana@example.test')
            ->assertSessionMissing('_old_input.password');

        $this->assertGuest();
    }

    public function test_login_error_is_shown_under_the_field_with_the_email_kept(): void
    {
        $this->admin();

        $this->from('/admin/login')
            ->followingRedirects()
            ->post('/admin/login', ['email' => 'jana@example.test', 'password' => 'spatne-heslo'])
            ->assertOk()
            ->assertSee(self::WRONG)
            ->assertSee('value="jana@example.test"', false)
            ->assertSee('aria-invalid="true" aria-describedby="email-error"', false);
    }

    public function test_correct_password_logs_in_regenerates_session_and_remembers(): void
    {
        $user = $this->admin();
        $this->startSession();
        $before = session()->getId();

        $this->post('/admin/login', ['email' => '  Jana@Example.TEST ', 'password' => 'spravne-heslo', 'remember' => '1'])
            ->assertRedirect(route('admin.inquiries.index'));

        $this->assertAuthenticatedAs($user);
        $this->assertNotSame($before, session()->getId());
        $this->assertNotNull($user->fresh()->remember_token);

        $this->get(route('admin.inquiries.index'))->assertOk()->assertSee('Jana Správcová')->assertSee('Odhlásit');
    }

    public function test_login_returns_to_the_page_the_guest_wanted(): void
    {
        $this->admin();
        $inquiry = InquiryFactory::new()->create();

        $this->get(route('admin.inquiries.show', $inquiry))->assertRedirect(route('admin.login'));
        $this->post('/admin/login', ['email' => 'jana@example.test', 'password' => 'spravne-heslo'])
            ->assertRedirect(route('admin.inquiries.show', $inquiry));
    }

    public function test_logged_in_user_skips_the_login_page(): void
    {
        $this->actingAs($this->admin())
            ->get('/admin/login')
            ->assertRedirect(route('admin.inquiries.index'));
    }

    public function test_logout(): void
    {
        $this->actingAs($this->admin())
            ->post('/admin/logout')
            ->assertRedirect(route('admin.login'));

        $this->assertGuest();
        $this->get('/admin/login')->assertSee('Odhlásili jste se.');
    }

    public function test_login_is_throttled_after_five_failed_attempts_per_email_and_ip(): void
    {
        Event::fake([Lockout::class]);
        $this->admin();
        $other = User::factory()->create(['email' => 'petr@example.test', 'password' => 'petrovo-heslo']);

        for ($i = 0; $i < 5; $i++) {
            $this->from('/admin/login')
                ->post('/admin/login', ['email' => 'jana@example.test', 'password' => 'spatne-'.$i])
                ->assertSessionHasErrors(['email' => self::WRONG]);
        }

        // 6. pokus – i se správným heslem je zablokovaný
        $response = $this->from('/admin/login')
            ->post('/admin/login', ['email' => 'jana@example.test', 'password' => 'spravne-heslo']);
        $response->assertSessionHasErrors('email');
        $this->assertStringStartsWith('Příliš mnoho pokusů o přihlášení. Zkuste to znovu za ', session('errors')->first('email'));
        $this->assertMatchesRegularExpression('/za \d+\x{00A0}(sekundu|sekundy|sekund)\.$/u', session('errors')->first('email'));
        $this->assertGuest();
        Event::assertDispatched(Lockout::class);

        // jiný účet ze stejné IP blokovaný není
        $this->post('/admin/login', ['email' => 'petr@example.test', 'password' => 'petrovo-heslo'])
            ->assertRedirect(route('admin.inquiries.index'));
        $this->assertAuthenticatedAs($other);
    }

    public function test_successful_login_resets_the_failed_attempt_counter(): void
    {
        $user = $this->admin();

        for ($i = 0; $i < 4; $i++) {
            $this->post('/admin/login', ['email' => 'jana@example.test', 'password' => 'spatne']);
        }
        $this->post('/admin/login', ['email' => 'jana@example.test', 'password' => 'spravne-heslo'])
            ->assertRedirect(route('admin.inquiries.index'));
        $this->post('/admin/logout');

        for ($i = 0; $i < 4; $i++) {
            $this->post('/admin/login', ['email' => 'jana@example.test', 'password' => 'spatne']);
        }
        $this->post('/admin/login', ['email' => 'jana@example.test', 'password' => 'spravne-heslo'])
            ->assertRedirect(route('admin.inquiries.index'));
        $this->assertAuthenticatedAs($user);
    }
}
