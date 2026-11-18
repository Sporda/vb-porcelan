@php
    $bare = $bare ?? false;
    $shopName = (string) config('shop.name', 'Bílé zlato');
    $nameParts = explode(' ', $shopName);
    $nameLast = count($nameParts) > 1 ? array_pop($nameParts) : null;
    $nameFirst = implode(' ', $nameParts);
    $assetVersion = fn (string $path) => @filemtime(public_path($path)) ?: '1';
    $flashAt = session('flash_at');
@endphp
<!doctype html>
<html lang="cs">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <meta name="theme-color" content="#24282b">
    <meta name="description" content="Administrace e-shopu {{ $shopName }}: poptávky zákazníků.">
    <title>@yield('title', 'Administrace') · {{ $shopName }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=EB+Garamond:ital,wght@0,400;0,500;1,400&family=Jost:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('assets/admin/admin.css') }}?v={{ $assetVersion('assets/admin/admin.css') }}">
    <script src="{{ asset('assets/admin/admin.js') }}?v={{ $assetVersion('assets/admin/admin.js') }}" defer></script>
</head>
<body class="{{ $bare ? 'is-bare' : 'is-app' }}">
@include('admin.partials.icons')
<a class="skip-link" href="#obsah">Přeskočit na obsah</a>

@unless ($bare)
    <header class="bar">
        <div class="wrap bar-in">
            <a class="brand" href="{{ route('admin.inquiries.index') }}" aria-label="{{ $shopName }} – administrace, přehled poptávek">
                <span class="brand-name">{{ $nameFirst }}@if ($nameLast) <em>{{ $nameLast }}</em>@endif</span>
                <span class="brand-sub">Administrace</span>
            </a>
            <div class="bar-right">
                <a class="bar-link" href="{{ url('/') }}" target="_blank" rel="noopener" aria-label="Zobrazit e-shop (otevře se v nové záložce)">
                    <svg class="ic" aria-hidden="true"><use href="#i-external"/></svg><span class="hide-sm">Zobrazit e-shop</span>
                </a>
                @auth
                    <span class="bar-user hide-md" title="Přihlášený účet">
                        <svg class="ic" aria-hidden="true"><use href="#i-user"/></svg>{{ auth()->user()->name }}
                    </span>
                    <form class="bar-form" method="POST" action="{{ route('admin.logout') }}">
                        @csrf
                        <button class="bar-link" type="submit">
                            <svg class="ic" aria-hidden="true"><use href="#i-logout"/></svg><span class="bar-text">Odhlásit</span>
                        </button>
                    </form>
                @endauth
            </div>
        </div>
    </header>
@endunless

<main id="obsah" class="{{ $bare ? 'main-bare' : 'main' }}">
    @unless ($bare)
        @if (! $flashAt && (session('success') || session('error')))
            <div class="wrap flash-wrap">
                @include('admin.partials.flash')
            </div>
        @endif
    @endunless

    @yield('content')
</main>
</body>
</html>
