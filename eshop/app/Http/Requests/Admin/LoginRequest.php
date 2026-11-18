<?php

namespace App\Http\Requests\Admin;

use App\Http\Controllers\Admin\Support\Format;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/** Přihlášení do administrace. Nejvýš 5 neúspěšných pokusů za minutu na kombinaci e-mail + IP. */
class LoginRequest extends FormRequest
{
    public const MAX_ATTEMPTS = 5;

    public const DECAY_SECONDS = 60;

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['email' => Str::lower(trim((string) $this->input('email')))]);
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email', 'max:255'],
            'password' => ['required', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.required' => 'Vyplňte e-mail.',
            'email.email' => 'Zadejte e-mail ve tvaru jmeno@domena.cz.',
            'email.max' => 'E-mail je příliš dlouhý.',
            'email.string' => 'Zadejte e-mail ve tvaru jmeno@domena.cz.',
            'password.required' => 'Vyplňte heslo.',
            'password.string' => 'Vyplňte heslo.',
            'password.max' => 'Heslo je příliš dlouhé.',
        ];
    }

    /** @throws ValidationException */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        if (! Auth::attempt($this->only('email', 'password'), $this->boolean('remember'))) {
            RateLimiter::hit($this->throttleKey(), self::DECAY_SECONDS);

            throw ValidationException::withMessages([
                'email' => 'E-mail nebo heslo nesouhlasí. Zkontrolujte je a zkuste to znovu.',
            ]);
        }

        RateLimiter::clear($this->throttleKey());
    }

    /** @throws ValidationException */
    public function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), self::MAX_ATTEMPTS)) {
            return;
        }

        event(new Lockout($this));

        $seconds = max(1, RateLimiter::availableIn($this->throttleKey()));

        throw ValidationException::withMessages([
            'email' => 'Příliš mnoho pokusů o přihlášení. Zkuste to znovu za '
                .Format::plural($seconds, 'sekundu', 'sekundy', 'sekund').'.',
        ]);
    }

    public function throttleKey(): string
    {
        return 'admin-login:'.Str::transliterate(Str::lower((string) $this->input('email')).'|'.$this->ip());
    }
}
