@extends('layouts.admin', ['bare' => true])

@section('title', 'Přihlášení')

@php
    $shopName = (string) config('shop.name', 'Bílé zlato');
    $parts = explode(' ', $shopName);
    $last = count($parts) > 1 ? array_pop($parts) : null;
    $emailError = $errors->first('email');
    $passwordError = $errors->first('password');
@endphp

@section('content')
    <div class="login">
        <div class="login-card">
            <div class="login-media">
                <img src="https://images.pexels.com/photos/18977900/pexels-photo-18977900.jpeg?auto=compress&cs=tinysrgb&w=900"
                     srcset="https://images.pexels.com/photos/18977900/pexels-photo-18977900.jpeg?auto=compress&cs=tinysrgb&w=600 600w,
                             https://images.pexels.com/photos/18977900/pexels-photo-18977900.jpeg?auto=compress&cs=tinysrgb&w=900 900w"
                     sizes="(max-width: 860px) 100vw, 480px"
                     width="900" height="1200" alt="Bílý porcelánový servis s reliéfním okrajem a šálkem, naskládaný na lněném ubrusu">
            </div>

            <div class="login-body">
                <a class="wordmark" href="{{ url('/') }}" aria-label="{{ $shopName }} – na úvodní stránku e-shopu">
                    <span class="wm-name">{{ implode(' ', $parts) }}@if ($last) <em>{{ $last }}</em>@endif</span>
                    <span class="wm-sub">Administrace e-shopu</span>
                </a>

                <h1 class="login-title">Přihlášení</h1>
                <p class="login-lead">Poptávky zákazníků na jednom místě. Účet vám založí správce webu.</p>

                @include('admin.partials.flash')

                <form class="form" method="POST" action="{{ route('admin.login.store') }}" novalidate>
                    @csrf

                    <div class="field">
                        <label class="field-label" for="email">E-mail</label>
                        <input class="input" id="email" name="email" type="email" value="{{ old('email') }}"
                               autocomplete="username" inputmode="email" autocapitalize="none" spellcheck="false" required
                               @if ($emailError) aria-invalid="true" aria-describedby="email-error" autofocus @elseif (! old('email')) autofocus @endif>
                        @if ($emailError)
                            <p class="field-error" id="email-error"><svg class="ic" aria-hidden="true"><use href="#i-alert"/></svg>{{ $emailError }}</p>
                        @endif
                    </div>

                    <div class="field">
                        <label class="field-label" for="password">Heslo</label>
                        <input class="input" id="password" name="password" type="password" autocomplete="current-password" required
                               @if ($passwordError) aria-invalid="true" aria-describedby="password-error" @endif
                               @if (old('email') && ! $emailError) autofocus @endif>
                        @if ($passwordError)
                            <p class="field-error" id="password-error"><svg class="ic" aria-hidden="true"><use href="#i-alert"/></svg>{{ $passwordError }}</p>
                        @endif
                    </div>

                    <label class="check">
                        <input type="checkbox" name="remember" value="1" @checked(old('remember'))>
                        <span>Zapamatovat si mě na tomto zařízení</span>
                    </label>

                    <button class="btn btn-primary btn-block" type="submit" data-busy="Přihlašuji…">Přihlásit se</button>
                </form>

                <a class="back-shop" href="{{ url('/') }}">
                    <svg class="ic" aria-hidden="true"><use href="#i-arrow-l"/></svg>Zpět do e-shopu
                </a>
            </div>
        </div>
    </div>
@endsection
