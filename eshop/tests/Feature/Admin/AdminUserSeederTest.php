<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Database\Seeders\AdminUserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminUserSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_creates_the_first_admin_from_config_only_once(): void
    {
        config(['shop.admin' => ['email' => ' Majitel@Example.TEST ', 'name' => 'Majitel obchodu', 'password' => 'tajne-heslo-123']]);

        $this->seed(AdminUserSeeder::class);
        $this->seed(AdminUserSeeder::class);

        $this->assertSame(1, User::query()->count());
        $user = User::query()->firstOrFail();
        $this->assertSame('majitel@example.test', $user->email);
        $this->assertSame('Majitel obchodu', $user->name);
        $this->assertTrue(Hash::check('tajne-heslo-123', $user->password));

        $this->post('/admin/login', ['email' => 'majitel@example.test', 'password' => 'tajne-heslo-123'])
            ->assertRedirect(route('admin.inquiries.index'));
    }

    public function test_never_overwrites_an_existing_user(): void
    {
        User::factory()->create(['email' => 'majitel@example.test', 'name' => 'Původní jméno', 'password' => 'puvodni-heslo']);
        config(['shop.admin' => ['email' => 'majitel@example.test', 'name' => 'Nové jméno', 'password' => 'nove-heslo-z-env']]);

        $this->seed(AdminUserSeeder::class);

        $user = User::query()->where('email', 'majitel@example.test')->firstOrFail();
        $this->assertSame(1, User::query()->count());
        $this->assertSame('Původní jméno', $user->name);
        $this->assertTrue(Hash::check('puvodni-heslo', $user->password));
        $this->assertFalse(Hash::check('nove-heslo-z-env', $user->password));
    }

    public function test_skips_when_email_or_password_is_missing(): void
    {
        config(['shop.admin' => ['email' => 'majitel@example.test', 'name' => null, 'password' => null]]);
        $this->seed(AdminUserSeeder::class);

        config(['shop.admin' => ['email' => null, 'name' => null, 'password' => 'heslo-bez-emailu']]);
        $this->seed(AdminUserSeeder::class);

        $this->assertSame(0, User::query()->count());
    }

    public function test_default_name_when_none_is_configured(): void
    {
        config(['shop.admin' => ['email' => 'admin@example.test', 'name' => '', 'password' => 'tajne-heslo-123']]);

        $this->seed(AdminUserSeeder::class);

        $this->assertSame('Administrátor', User::query()->firstOrFail()->name);
    }
}
