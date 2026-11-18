<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

/**
 * Založí nebo upraví účet do administrace.
 *   php artisan shop:admin jana@firma.cz --name="Jana Nováková" --password="…"
 * Bez --password vygeneruje silné heslo a jednou ho vypíše.
 */
class ShopAdminCommand extends Command
{
    protected $signature = 'shop:admin
        {email : E-mail účtu (slouží k přihlášení)}
        {--name= : Jméno zobrazené v administraci}
        {--password= : Nové heslo (aspoň 10 znaků); bez něj se vygeneruje}';

    protected $description = 'Založí nebo upraví účet do administrace (/admin) a nastaví mu heslo';

    public const MIN_PASSWORD = 10;

    public function handle(): int
    {
        $email = Str::lower(trim((string) $this->argument('email')));
        $name = trim((string) $this->option('name'));
        $password = (string) $this->option('password');
        $generated = $password === '';

        if (Validator::make(['email' => $email], ['email' => ['required', 'email', 'max:255']])->fails()) {
            $this->error("„{$email}“ není platný e-mail.");

            return self::FAILURE;
        }

        if (! $generated && mb_strlen($password) < self::MIN_PASSWORD) {
            $this->error('Heslo musí mít aspoň '.self::MIN_PASSWORD.' znaků (nebo --password vynechte a heslo se vygeneruje).');

            return self::FAILURE;
        }

        if ($generated) {
            $password = Str::password(20, letters: true, numbers: true, symbols: false, spaces: false);
        }

        $user = User::query()->where('email', $email)->first();
        $created = $user === null;

        $user ??= new User(['email' => $email]);
        if ($name !== '' || $created) {
            $user->name = $name !== '' ? $name : ($user->name ?: 'Administrátor');
        }
        $user->password = $password;           // cast „hashed“
        $user->remember_token = Str::random(60); // zneplatní „Zapamatovat“ na ostatních zařízeních
        $user->save();

        if (! $created) {
            $this->logOutOtherSessions($user);
        }

        $this->info(($created ? 'Účet založen: ' : 'Účet upraven: ').$user->email.' ('.$user->name.')');

        if ($generated) {
            $this->newLine();
            $this->line('  Heslo: <options=bold>'.$password.'</>');
            $this->line('  Uložte si ho – znovu se už nezobrazí.');
            $this->newLine();
        } else {
            $this->line('  Heslo je nastavené.');
        }

        return self::SUCCESS;
    }

    /** Při databázových relacích odhlásí účet všude (nové heslo platí hned). */
    private function logOutOtherSessions(User $user): void
    {
        if (config('session.driver') !== 'database') {
            return;
        }

        $table = (string) config('session.table', 'sessions');
        if (Schema::hasTable($table)) {
            DB::table($table)->where('user_id', $user->getKey())->delete();
        }
    }
}
