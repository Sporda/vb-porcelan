@php
    use App\View\Fmt;
    $shopName = config('shop.name', 'Bílé zlato');
    $defaultDescription = 'Nové, originální talíře, šálky i jídelní sety Villeroy & Boch přímo z výroby, až o 45 % pod doporučenou cenou.';
    // Sekce jsou už escapované (inline @section), proto se níže vypisují přes {!! !!}.
    $ogImage = trim($__env->yieldContent('og_image')) ?: e('https://images.pexels.com/photos/18977900/pexels-photo-18977900.jpeg?auto=compress&cs=tinysrgb&w=1200');
    $pageTitle = trim($__env->yieldContent('title'));
    $fullTitle = $pageTitle !== '' ? $pageTitle.' – '.e($shopName) : e($shopName.' – originální porcelán Villeroy & Boch');
    $currentCategory = request()->route('category');
    $currentCategory = $currentCategory instanceof \App\Models\Category ? $currentCategory : null;
    $contactEmail = config('shop.contact.email');
@endphp
<!doctype html>
<html lang="cs" data-type="product">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>{!! $fullTitle !!}</title>
<meta name="description" content="@yield('description', $defaultDescription)">
@unless (config('shop.indexable'))
<meta name="robots" content="noindex, nofollow">
@endunless
<link rel="canonical" href="@yield('canonical', url()->current())">
<meta property="og:type" content="@yield('og_type', 'website')">
<meta property="og:site_name" content="{{ $shopName }}">
<meta property="og:locale" content="cs_CZ">
<meta property="og:title" content="{!! $fullTitle !!}">
<meta property="og:description" content="@yield('description', $defaultDescription)">
<meta property="og:url" content="@yield('canonical', url()->current())">
<meta property="og:image" content="{!! $ogImage !!}">
<meta name="twitter:card" content="summary_large_image">
<meta name="theme-color" content="#dde4d6">
<meta name="page-type" content="product">
<meta name="site-kind" content="other">
<link rel="icon" href="{{ asset('favicon.ico') }}" sizes="32x32">
<link rel="icon" href="{{ asset('assets/img/icon.svg') }}" type="image/svg+xml">
<link rel="apple-touch-icon" href="{{ asset('assets/img/apple-touch-icon.png') }}">
<link rel="manifest" href="{{ asset('assets/site.webmanifest') }}">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="preconnect" href="https://images.pexels.com">
<link href="https://fonts.googleapis.com/css2?family=EB+Garamond:ital,wght@0,400;0,500;1,400;1,500&family=Jost:wght@300;400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="{{ Fmt::asset('assets/css/shop.css') }}">
<script>document.documentElement.classList.add('js','m-enter');setTimeout(function(){document.documentElement.classList.add('m-enter-done')},700)</script>
@stack('head')
</head>
<body class="@yield('body_class')">
@include('shop.partials.icons')

<a class="skip-link" href="#main">Přeskočit na obsah</a>

<div class="topbar">
  <div class="wrap topbar-in">
    <span>Doprava zdarma od {{ Fmt::price(config('shop.free_shipping_from')) }}</span>
  </div>
</div>

<header class="site-header">
  <div class="wrap header-in">
    <div class="header-left">
      <button class="icon-btn menu-btn" type="button" aria-label="Otevřít menu" aria-expanded="false" aria-controls="drawer" data-open-drawer><svg class="ic" aria-hidden="true"><use href="#i-menu"/></svg></button>
      <nav class="util-nav" aria-label="Informace">
        <a href="{{ route('pages.why') }}" @if (request()->routeIs('pages.why')) aria-current="page" @endif>Proč tak levně</a>
        <a href="{{ route('shop.collections') }}" @if (request()->routeIs('shop.collections', 'shop.collection')) aria-current="page" @endif>Kolekce</a>
        <a href="{{ route('pages.contact') }}" @if (request()->routeIs('pages.contact')) aria-current="page" @endif>Kontakt</a>
      </nav>
    </div>
    <a class="wordmark" href="{{ route('home') }}" aria-label="{{ $shopName }} – na úvodní stránku">
      <span class="wm-name">Bílé <em>zlato</em></span>
      <span class="wm-sub">{{ config('shop.tagline') }}</span>
    </a>
    <div class="header-right">
      <a class="icon-btn" href="{{ route('shop.search') }}" aria-label="Hledat" aria-expanded="false" aria-controls="search" data-search-toggle><svg class="ic" aria-hidden="true"><use href="#i-search"/></svg></a>
      <a class="icon-btn" href="{{ route('favorites.index') }}" aria-label="Oblíbené{{ $favCount ? ', '.$favCount : '' }}" data-fav-link><svg class="ic" aria-hidden="true"><use href="#i-heart"/></svg><span class="cart-count" data-fav-count aria-hidden="true" @if (! $favCount) hidden @endif>{{ $favCount }}</span></a>
      <a class="icon-btn" href="{{ route('cart.show') }}" aria-label="Košík, {{ $cartCount }} {{ Fmt::plural($cartCount, 'kus', 'kusy', 'kusů') }}" data-cart-link><svg class="ic" aria-hidden="true"><use href="#i-bag"/></svg><span class="cart-count" data-cart-count aria-hidden="true" @if (! $cartCount) hidden @endif>{{ $cartCount }}</span></a>
    </div>
  </div>
  <nav class="cat-nav" aria-label="Kategorie">
    <ul class="wrap">
      @foreach ($navCategories as $c)
        <li><a href="{{ route('shop.category', $c) }}" @if ($currentCategory && $currentCategory->is($c)) aria-current="page" @endif>{{ $c->name }}</a></li>
      @endforeach
      <li><a class="is-sale" href="{{ route('shop.sale') }}" @if (request()->routeIs('shop.sale')) aria-current="page" @endif>Výprodej</a></li>
    </ul>
  </nav>
  <div class="search-panel" id="search" hidden>
    <div class="wrap">
      <form class="search-form" role="search" method="get" action="{{ route('shop.search') }}">
        <svg class="ic" aria-hidden="true"><use href="#i-search"/></svg>
        <label class="sr-only" for="q-header">Hledat v obchodě</label>
        <input id="q-header" name="q" type="search" placeholder="Kolekce nebo kus, třeba Anmut či espresso šálek" autocomplete="off" maxlength="80">
        <button class="icon-btn" type="button" aria-label="Zavřít hledání" data-search-close><svg class="ic" aria-hidden="true"><use href="#i-close"/></svg></button>
      </form>
      <p class="search-hints"><span>Často hledáte:</span>
        <a href="{{ route('shop.search', ['q' => 'Anmut Gold']) }}">Anmut Gold</a>
        <a href="{{ route('shop.search', ['q' => 'espresso']) }}">Espresso šálky</a>
        <a href="{{ route('shop.search', ['q' => 'jídelní sada']) }}">Jídelní sady</a>
        <a href="{{ route('shop.search', ['q' => 'konvice']) }}">Konvice</a>
      </p>
    </div>
  </div>
</header>

<main id="main">
  @if (session('status'))
    <div class="wrap flash-wrap"><p class="flash" role="status"><svg class="ic" aria-hidden="true"><use href="#i-check"/></svg><span>{{ session('status') }}</span>@if (session('cart_added')) <a href="{{ route('cart.show') }}">Zobrazit košík</a>@endif</p></div>
  @endif
  @yield('content')
</main>

<footer class="footer">
  <div class="wrap">
    <div class="footer-grid">
      <div class="footer-about">
        <a class="wordmark" href="{{ route('home') }}" aria-label="{{ $shopName }} – na úvodní stránku"><span class="wm-name">Bílé <em>zlato</em></span><span class="wm-sub">{{ config('shop.tagline') }}</span></a>
        <p>Originální porcelán Villeroy &amp; Boch přímo z&nbsp;výroby. Nové kusy za ceny, které dávají smysl.</p>
      </div>
      <div>
        <p class="footer-h">Nakupovat</p>
        <ul>
          @foreach ($navCategories->take(5) as $c)
            <li><a href="{{ route('shop.category', $c) }}">{{ $c->name }}</a></li>
          @endforeach
          <li><a href="{{ route('shop.sale') }}">Výprodej</a></li>
        </ul>
      </div>
      <div>
        <p class="footer-h">Zákazník</p>
        <ul>
          <li><a href="{{ route('pages.delivery') }}">Doprava a platba</a></li>
          <li><a href="{{ route('pages.returns') }}">Reklamace a vrácení</a></li>
          <li><a href="{{ route('favorites.index') }}">Oblíbené</a></li>
          <li><a href="{{ route('cart.show') }}">Košík</a></li>
        </ul>
      </div>
      <div>
        <p class="footer-h">O nás</p>
        <ul>
          <li><a href="{{ route('pages.why') }}">Proč tak levně</a></li>
          <li><a href="{{ route('pages.about') }}">O obchodě</a></li>
          <li><a href="{{ route('pages.terms') }}">Obchodní podmínky</a></li>
          <li><a href="{{ route('pages.privacy') }}">Ochrana osobních údajů</a></li>
        </ul>
      </div>
      <div class="footer-contact">
        <p class="footer-h">Kontakt</p>
        <p>{{ config('shop.contact.hours') }} h<br><a class="text-link" href="mailto:{{ $contactEmail }}">Napsat e-mail</a> · <a class="text-link" href="{{ route('pages.contact') }}">Kontakt</a></p>
        <p class="pay">Platíte až po potvrzení objednávky – převodem nebo na dobírku.</p>
      </div>
    </div>
    <div class="footer-bottom">
      <p>© {{ now()->year }} {{ $shopName }} · Villeroy &amp; Boch je ochranná známka svého vlastníka. {{ $shopName }} je nezávislý prodejce.</p>
      <p>Ukázková verze e-shopu – objednávka se odešle jako nezávazná poptávka · Ilustrační fotografie: Pexels</p>
    </div>
  </div>
</footer>

<div class="overlay" id="overlay" data-close-all></div>
<aside class="drawer" id="drawer" aria-label="Menu" aria-hidden="true">
  <div class="drawer-head">
    <a class="wordmark" href="{{ route('home') }}" aria-label="{{ $shopName }} – na úvodní stránku"><span class="wm-name">Bílé <em>zlato</em></span></a>
    <button class="icon-btn" type="button" aria-label="Zavřít menu" data-close-all><svg class="ic" aria-hidden="true"><use href="#i-close"/></svg></button>
  </div>
  <nav aria-label="Kategorie v menu">
    @foreach ($navCategories as $c)
      <a href="{{ route('shop.category', $c) }}">{{ $c->name }} <svg class="ic" aria-hidden="true"><use href="#i-chev-r"/></svg></a>
    @endforeach
    <a class="sale" href="{{ route('shop.sale') }}">Výprodej <svg class="ic" aria-hidden="true"><use href="#i-chev-r"/></svg></a>
  </nav>
  <div class="util">
    <a href="{{ route('shop.index') }}">Všechny produkty</a>
    <a href="{{ route('shop.collections') }}">Kolekce</a>
    <a href="{{ route('pages.why') }}">Proč tak levně</a>
    <a href="{{ route('favorites.index') }}">Oblíbené</a>
    <a href="{{ route('pages.contact') }}">Kontakt</a>
  </div>
</aside>

<div class="toast" id="toast" role="status" aria-live="polite">
  <img id="toastImg" src="data:image/gif;base64,R0lGODlhAQABAAAAACw=" width="56" height="70" alt="">
  <div>
    <p class="t1"><svg class="ic" aria-hidden="true"><use href="#i-check"/></svg> <span id="toastTitle">Přidáno do košíku</span></p>
    <p class="t2" id="toastName"></p>
    <p class="t3"><span id="toastMeta"></span><a href="{{ route('cart.show') }}">Zobrazit košík</a></p>
  </div>
</div>

@stack('jsonld')
<script src="{{ Fmt::asset('assets/js/shop.js') }}" defer></script>
</body>
</html>
