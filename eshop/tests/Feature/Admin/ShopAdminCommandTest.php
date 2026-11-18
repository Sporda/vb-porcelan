<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ShopAdminCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_creates_a_user_with_the_given_password(): void
    {
        $this->artisan('shop:admin', ['email' => 'Nova@Example.test', '--name' => 'Nová Správkyně', '--password' => 'dlouhe-heslo-123'])
            ->expectsOutputToContain('Účet založen: nova@example.test (Nová Správkyně)')
            ->doesntExpectOutputToContain('dlouhe-heslo-123')
            ->assertSuccessful();

        $user = User::query()->where('email', 'nova@example.test')->firstOrFail();
        $this->assertSame('Nová Správkyně', $user->name);
        $this->assertTrue(Hash::check('dlouhe-heslo-123', $user->password));
    }

    public function test_generates_a_strong_password_and_prints_it_once(): void
    {
        $exit = Artisan::call('shop:admin', ['email' => 'gen@example.test']);
        $output = Artisan::output();

        $this->assertSame(0, $exit);
        $this->assertSame(1, preg_match('/Heslo: (\S+)/', $output, $m), $output);
        $password = $m[1];
        $this->assertSame(20, strlen($password));
        $this->assertSame(1, substr_count($output, $password));
        $this->assertStringContainsString('znovu se už nezobrazí', $output);

        $user = User::query()->where('email', 'gen@example.test')->firstOrFail();
        $this->assertSame('Administrátor', $user->name);
        $this->assertTrue(Hash::check($password, $user->password));

        $this->post('/admin/login', ['email' => 'gen@example.test', 'password' => $password])
            ->assertRedirect(route('admin.inquiries.index'));
    }

    public function test_updates_an_existing_user(): void
    {
        $user = User::factory()->create(['email' => 'jana@example.test', 'name' => 'Jana', 'password' => 'stare-heslo', 'remember_token' => 'stary-token']);

        $this->artisan('shop:admin', ['email' => 'jana@example.test', '--password' => 'nove-heslo-456'])
            ->expectsOutputToContain('Účet upraven: jana@example.test (Jana)')
            ->assertSuccessful();

        $user->refresh();
        $this->assertSame(1, User::query()->count());
        $this->assertSame('Jana', $user->name); // bez --name se jméno nemění
        $this->assertTrue(Hash::check('nove-heslo-456', $user->password));
        $this->assertNotSame('stary-token', $user->remember_token);

        $this->artisan('shop:admin', ['email' => 'jana@example.test', '--name' => 'Jana Nováková', '--password' => 'nove-heslo-789'])
            ->assertSuccessful();
        $this->assertSame('Jana Nováková', $user->fresh()->name);
    }

    public function test_rejects_invalid_email_and_short_password(): void
    {
        $this->artisan('shop:admin', ['email' => 'neni-email'])
            ->expectsOutputToContain('není platný e-mail')
            ->assertFailed();

        $this->artisan('shop:admin', ['email' => 'jana@example.test', '--password' => 'kratke'])
            ->expectsOutputToContain('aspoň 10 znaků')
            ->assertFailed();

        $this->assertSame(0, User::query()->count());
    }
}
