<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * První účet do administrace z .env (ADMIN_EMAIL, ADMIN_NAME, ADMIN_PASSWORD → config('shop.admin')).
 * Idempotentní: založí účet jen tehdy, když e-mail i heslo jsou vyplněné a účet s tím e-mailem ještě není.
 * Existující účet nikdy nemění (heslo se mění příkazem `php artisan shop:admin`).
 */
class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $email = Str::lower(trim((string) config('shop.admin.email')));
        $password = (string) config('shop.admin.password');

        if ($email === '' || $password === '') {
            $this->command?->line('  Účet administrace: ADMIN_EMAIL / ADMIN_PASSWORD nejsou vyplněné – přeskočeno.');

            return;
        }

        if (User::query()->where('email', $email)->exists()) {
            $this->command?->line("  Účet administrace {$email} už existuje – beze změny.");

            return;
        }

        $name = trim((string) config('shop.admin.name'));

        User::query()->create([
            'name' => $name !== '' ? $name : 'Administrátor',
            'email' => $email,
            'password' => $password, // model ho zahashuje (cast „hashed“)
        ]);

        $this->command?->info("  Účet administrace {$email} založen.");
    }
}
