@php
    use App\View\Fmt;
    use App\Services\Cart;
    $lines = $cart->lines();
@endphp
@if ($lines->isEmpty())
  <div class="empty">
    <p class="empty-t">Košík je prázdný</p>
    <p>Vyberte si z&nbsp;talířů, šálků a&nbsp;jídelních sad. Doprava je zdarma od {{ Fmt::price($cart->freeShippingFrom()) }}.</p>
    <p class="empty-links">
      <a class="btn btn-primary" href="{{ route('shop.index') }}">Prohlédnout porcelán</a>
      <a class="btn btn-ghost" href="{{ route('shop.sale') }}">Výprodej</a>
    </p>
  </div>
@else
  <div class="cart-grid">
    <div class="cart-main">
      <ul class="cart-lines">
        @foreach ($lines as $line)
          @php
              $p = $line->product;
              $url = route('shop.product', $p).($line->optionCode() !== '' && count($p->options ?? []) > 1 ? '?baleni='.urlencode($line->optionCode()) : '');
              $img = $p->mainImage();
          @endphp
          <li class="cart-line" data-line="{{ $line->key }}">
            <a class="cl-media" href="{{ $url }}" tabindex="-1" aria-hidden="true">
              @if ($img)<img src="{{ Fmt::img($img['url'], 240) }}" width="240" height="300" loading="lazy" alt="" @if (! empty($img['focus'])) style="object-position: {{ $img['focus'] }}" @endif>@endif
            </a>
            <div class="cl-info">
              @if ($line->collectionName())<p class="card-coll">{{ $line->collectionName() }}</p>@endif
              <h2 class="cl-name"><a href="{{ $url }}">{{ $p->name }}</a></h2>
              <p class="cl-meta">
                @if ($line->optionLabel())<span>{{ $line->optionLabel() }}</span> · @endif
                <span>{{ Fmt::price($line->unitPrice()) }} / {{ $line->units() > 1 ? 'balení' : 'ks' }}</span>
                @if ($p->stock === 'on_order') · <span class="cl-stock">na objednávku</span>@endif
              </p>
            </div>
            <form class="cl-qty" method="post" action="{{ route('cart.update') }}" data-cart-form>
              @csrf
              <input type="hidden" name="key" value="{{ $line->key }}">
              <button type="submit" name="qty" value="{{ $line->qty - 1 }}" aria-label="{{ $line->qty > 1 ? 'Snížit množství' : 'Odebrat z košíku' }}: {{ $p->name }}"><svg class="ic" aria-hidden="true"><use href="#i-{{ $line->qty > 1 ? 'minus' : 'trash' }}"/></svg></button>
              <span class="cl-n" aria-label="Množství">{{ $line->qty }}</span>
              <button type="submit" name="qty" value="{{ min(Cart::MAX_QTY, $line->qty + 1) }}" aria-label="Zvýšit množství: {{ $p->name }}" @disabled($line->qty >= Cart::MAX_QTY)><svg class="ic" aria-hidden="true"><use href="#i-plus"/></svg></button>
            </form>
            <div class="cl-total">
              <b>{{ Fmt::price($line->lineTotal()) }}</b>
              @if ($line->lineRrp() > $line->lineTotal())<s><span class="sr-only">Doporučená cena </span>{{ Fmt::price($line->lineRrp()) }}</s>@endif
            </div>
            <form class="cl-remove" method="post" action="{{ route('cart.remove') }}" data-cart-form>
              @csrf
              <input type="hidden" name="key" value="{{ $line->key }}">
              <button type="submit" aria-label="Odebrat z košíku: {{ $p->name }}"><svg class="ic" aria-hidden="true"><use href="#i-close"/></svg></button>
            </form>
          </li>
        @endforeach
      </ul>
      <div class="cart-after">
        <a class="link-arrow" href="{{ route('shop.index') }}"><svg class="ic flip" aria-hidden="true"><use href="#i-arrow"/></svg> Pokračovat v nákupu</a>
        <form method="post" action="{{ route('cart.clear') }}" data-cart-form>
          @csrf
          <button class="text-btn" type="submit">Vyprázdnit košík</button>
        </form>
      </div>
    </div>

    <aside class="summary" aria-labelledby="h-summary">
      <h2 id="h-summary" class="summary-h">Souhrn</h2>
      <div class="ship-bar{{ $cart->hasFreeShipping() ? ' is-free' : '' }}">
        @if ($cart->hasFreeShipping())
          <p><svg class="ic" aria-hidden="true"><use href="#i-truck"/></svg> Máte dopravu zdarma.</p>
        @else
          <p><svg class="ic" aria-hidden="true"><use href="#i-truck"/></svg> Do dopravy zdarma zbývá <b>{{ Fmt::price($cart->remainingForFreeShipping()) }}</b>.</p>
        @endif
        <div class="ship-track" role="progressbar" aria-label="Cesta k dopravě zdarma" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $cart->freeShippingProgress() }}"><span style="width:{{ $cart->freeShippingProgress() }}%"></span></div>
      </div>
      <dl class="sum-rows">
        <div><dt>Zboží ({{ Fmt::count($cart->count(), 'kus', 'kusy', 'kusů') }})</dt><dd>{{ Fmt::price($cart->itemsTotal()) }}</dd></div>
        @if ($cart->saving() > 0)
          <div class="muted"><dt>Doporučená cena</dt><dd><s>{{ Fmt::price($cart->rrpTotal()) }}</s></dd></div>
          <div class="save"><dt>Ušetříte</dt><dd>{{ Fmt::price($cart->saving()) }}</dd></div>
        @endif
        <div><dt>Doprava</dt><dd>{{ $cart->hasFreeShipping() ? 'zdarma' : 'od '.Fmt::price($cart->cheapestPaidShipping()) }}</dd></div>
        <div class="total"><dt>{{ $cart->hasFreeShipping() ? 'Celkem' : 'Celkem za zboží' }}</dt><dd>{{ Fmt::price($cart->itemsTotal()) }}</dd></div>
      </dl>
      @unless ($cart->hasFreeShipping())
        <p class="sum-note">Osobní odběr v&nbsp;Praze je vždy zdarma. Způsob dopravy vyberete v&nbsp;dalším kroku.</p>
      @endunless
      <a class="btn btn-primary btn-block" href="{{ route('checkout.show') }}">Pokračovat k objednávce <svg class="ic" aria-hidden="true"><use href="#i-arrow"/></svg></a>
      <p class="sum-note">Nic teď neplatíte – odešlete nezávaznou poptávku a&nbsp;my ověříme dostupnost.</p>
    </aside>
  </div>
@endif
