@extends('layouts.shop')
@php
    use App\View\Fmt;
    $lines = $cart->lines();
    $free = $cart->hasFreeShipping();
    $selected = old('delivery', array_key_first($delivery));
    if (! array_key_exists($selected, $delivery)) {
        $selected = array_key_first($delivery);
    }
    $selectedType = $delivery[$selected]['type'] ?? null;
    $ship = $cart->shippingFor($selected);
@endphp

@section('title', 'Objednávka – nezávazná poptávka')
@section('description', 'Dokončení objednávky porcelánu Villeroy & Boch. Nic teď neplatíte, odešlete nezávaznou poptávku.')

@section('content')
<div class="wrap">
  <nav class="crumbs" aria-label="Drobečková navigace">
    <a href="{{ route('home') }}">Domů</a><svg class="ic" aria-hidden="true"><use href="#i-chev-r"/></svg>
    <a href="{{ route('cart.show') }}">Košík</a><svg class="ic" aria-hidden="true"><use href="#i-chev-r"/></svg>
    <span aria-current="page">Objednávka</span>
  </nav>
  <header class="page-head">
    <p class="label">Nákup · krok 2 ze 2</p>
    <h1>Nezávazná <em>poptávka</em></h1>
  </header>

  <p class="notice"><svg class="ic" aria-hidden="true"><use href="#i-info"/></svg><span><b>Nic teď neplatíte</b> – odešlete nezávaznou poptávku, ověříme dostupnost a&nbsp;pošleme potvrzení s&nbsp;platebními údaji.</span></p>

  @if ($errors->any())
    <div class="error-box" role="alert" tabindex="-1" data-error-box>
      <p><b>Zkontrolujte prosím {{ $errors->count() === 1 ? 'zvýrazněné pole' : 'zvýrazněná pole' }}.</b></p>
      <ul>
        @foreach ($errors->keys() as $key)
          <li><a href="#f-{{ $key }}">{{ $errors->first($key) }}</a></li>
        @endforeach
      </ul>
    </div>
  @endif

  <form class="co-grid page-body" method="post" action="{{ route('checkout.store') }}" data-checkout>
    @csrf
    <div class="co-main">
      <fieldset class="co-sec">
        <legend><span class="sec-num">01</span> Kontakt</legend>
        <div class="field-grid">
          <x-field name="customer_name" label="Jméno a příjmení" autocomplete="name" maxlength="120" required class="span-2" />
          <x-field name="email" label="E-mail" type="email" autocomplete="email" maxlength="190" required hint="Sem pošleme potvrzení a platební údaje." />
          <x-field name="phone" label="Telefon" type="tel" autocomplete="tel" inputmode="tel" maxlength="25" required hint="Pro dopravce, např. +420 601 123 456." />
          <x-field name="company" label="Firma" autocomplete="organization" maxlength="120" optional />
          <x-field name="ic" label="IČO" inputmode="numeric" maxlength="10" optional />
        </div>
      </fieldset>

      <fieldset class="co-sec">
        <legend><span class="sec-num">02</span> Doprava</legend>
        @error('delivery')<p class="field-error" id="f-delivery">{{ $message }}</p>@enderror
        <div class="choices" role="radiogroup" aria-label="Způsob doručení">
          @foreach ($delivery as $code => $m)
            @php $price = $free ? 0 : (int) $m['price']; @endphp
            <label class="choice">
              <input type="radio" name="delivery" value="{{ $code }}" @checked($selected === $code) data-type="{{ $m['type'] }}" data-price="{{ $price }}" required>
              <span class="choice-body">
                <span class="choice-t">{{ $m['label'] }}</span>
                <span class="choice-s">{{ $m['eta'] }}</span>
              </span>
              <span class="choice-p">
                @if ($price === 0)
                  zdarma @if ($free && $m['price'] > 0)<s>{{ Fmt::price($m['price']) }}</s>@endif
                @else
                  {{ Fmt::price($price) }}
                @endif
              </span>
            </label>
          @endforeach
        </div>

        <div class="cond{{ $selectedType === 'pickup_point' ? ' is-on' : '' }}" data-for-type="pickup_point">
          <p class="cond-t">Jen pro Zásilkovnu</p>
          <x-field name="pickup_point" label="Výdejní místo Zásilkovny" maxlength="190" hint="Název nebo adresa výdejního místa, třeba „Praha 2, Vinohradská“." />
        </div>
        <div class="cond{{ $selectedType === 'address' ? ' is-on' : '' }}" data-for-type="address">
          <p class="cond-t">Jen pro doručení kurýrem</p>
          <div class="field-grid">
            <x-field name="street" label="Ulice a číslo domu" autocomplete="street-address" maxlength="150" class="span-2" />
            <x-field name="city" label="Obec" autocomplete="address-level2" maxlength="100" />
            <x-field name="zip" label="PSČ" autocomplete="postal-code" inputmode="numeric" maxlength="6" />
          </div>
        </div>
        <div class="cond{{ $selectedType === 'store' ? ' is-on' : '' }}" data-for-type="store">
          <p class="cond-t">Jen pro osobní odběr</p>
          <p class="cond-note">Vyzvednutí v&nbsp;Praze – přesnou adresu a&nbsp;čas domluvíme v&nbsp;potvrzovacím e-mailu.</p>
        </div>
      </fieldset>

      <fieldset class="co-sec">
        <legend><span class="sec-num">03</span> Poznámka a souhlas</legend>
        <x-field name="note" label="Poznámka k objednávce" type="textarea" rows="4" maxlength="2000" optional hint="Třeba termín, kdy porcelán potřebujete, nebo dotaz k dekoru." />
        <div class="hp" aria-hidden="true">
          <label for="f-website">Web (nevyplňujte)</label>
          <input id="f-website" name="website" type="text" tabindex="-1" autocomplete="off" value="">
        </div>
        <div class="field consent{{ $errors->has('consent') ? ' has-error' : '' }}">
          <label class="check" for="f-consent">
            <input id="f-consent" type="checkbox" name="consent" value="1" @checked(old('consent')) required @error('consent') aria-invalid="true" aria-describedby="f-consent-error" @enderror>
            <span>Souhlasím s&nbsp;<a class="text-link" href="{{ route('pages.terms') }}" target="_blank" rel="noopener">obchodními podmínkami</a> a&nbsp;se <a class="text-link" href="{{ route('pages.privacy') }}" target="_blank" rel="noopener">zpracováním osobních údajů</a> pro vyřízení poptávky.</span>
          </label>
          @error('consent')<p class="field-error" id="f-consent-error">{{ $message }}</p>@enderror
        </div>
      </fieldset>
    </div>

    <aside class="summary co-summary" aria-labelledby="h-co-sum">
      <h2 id="h-co-sum" class="summary-h">Vaše objednávka</h2>
      <ul class="mini-lines">
        @foreach ($lines as $line)
          @php $img = $line->product->mainImage(); @endphp
          <li>
            @if ($img)<img src="{{ Fmt::img($img['url'], 160) }}" width="56" height="70" loading="lazy" alt="" @if (! empty($img['focus'])) style="object-position: {{ $img['focus'] }}" @endif>@endif
            <div>
              <p class="ml-name">{{ $line->product->name }}</p>
              <p class="ml-meta">{{ $line->collectionName() }}{{ $line->optionLabel() ? ' · '.$line->optionLabel() : '' }} · {{ $line->qty }}&nbsp;×&nbsp;{{ Fmt::price($line->unitPrice()) }}</p>
            </div>
            <b>{{ Fmt::price($line->lineTotal()) }}</b>
          </li>
        @endforeach
      </ul>
      <p class="mini-edit"><a class="text-link" href="{{ route('cart.show') }}">Upravit košík</a></p>
      <dl class="sum-rows" data-items-total="{{ $cart->itemsTotal() }}">
        <div><dt>Zboží</dt><dd>{{ Fmt::price($cart->itemsTotal()) }}</dd></div>
        @if ($cart->saving() > 0)
          <div class="save"><dt>Ušetříte proti doporučené ceně</dt><dd>{{ Fmt::price($cart->saving()) }}</dd></div>
        @endif
        <div><dt>Doprava</dt><dd data-ship-value>{{ $ship === 0 ? 'zdarma' : Fmt::price($ship) }}</dd></div>
        <div class="total"><dt>Celkem</dt><dd data-total-value>{{ Fmt::price($cart->itemsTotal() + $ship) }}</dd></div>
      </dl>
      <button class="btn btn-primary btn-block" type="submit">Odeslat nezávaznou poptávku</button>
      <p class="sum-note">Platíte až po potvrzení – převodem nebo na dobírku. Poptávku můžete do potvrzení kdykoli zrušit.</p>
    </aside>
  </form>
</div>
@endsection
