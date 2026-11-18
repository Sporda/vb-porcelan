@extends('layouts.shop')
@php
    use App\View\Fmt;
    $p = $product;
    $images = $p->images ?? [];
    $main = $images[0] ?? null;
    $collection = $p->collection;
    $category = $p->category;
    $options = $p->options ?? [];
    $current = $p->option($selectedOption);
    $currentRrp = $current['rrp'] ?? ($p->rrp ? $p->rrp * max(1, (int) ($current['units'] ?? 1)) : null);
    $currentPrice = (int) $current['price'];
    $pct = $currentRrp && $currentRrp > $currentPrice ? (int) floor(100 * ($currentRrp - $currentPrice) / $currentRrp) : null;
    $optLabel = collect($options)->every(fn ($o) => (int) ($o['units'] ?? 1) === 1) ? 'Velikost' : 'Balení';
    $stockState = $p->stock;
    $stockText = match ($stockState) {
        'last_pieces' => 'Poslední kusy skladem'.($p->stock_qty ? ' ('.$p->stock_qty.' '.Fmt::plural($p->stock_qty, 'ks', 'ks', 'ks').')' : '').' · odesíláme do 24 hodin',
        'on_order' => 'Na objednávku · termín dodání potvrdíme e-mailem',
        default => 'Skladem · odesíláme do 24 hodin',
    };
    $title = ($collection ? $collection->name.' – ' : '').$p->name;
    $delivery = config('shop.delivery', []);
    $deliveryText = collect($delivery)->map(fn ($d) => $d['label'].' '.($d['price'] ? Fmt::price($d['price']) : 'zdarma'))->implode(', ');
    $availability = $stockState === 'on_order' ? 'https://schema.org/BackOrder' : ($stockState === 'last_pieces' ? 'https://schema.org/LimitedAvailability' : 'https://schema.org/InStock');
    $jsonLd = [
        '@context' => 'https://schema.org',
        '@type' => 'Product',
        'name' => $title,
        'description' => $p->subtitle ?: Str::limit((string) $p->description, 200),
        'sku' => $p->slug,
        'brand' => ['@type' => 'Brand', 'name' => 'Villeroy & Boch'],
        'category' => $category?->name,
        'image' => collect($images)->map(fn ($i) => Fmt::img($i['url'], 1200))->all(),
        'material' => $p->material,
        'offers' => [
            '@type' => 'Offer',
            'url' => route('shop.product', $p),
            'priceCurrency' => 'CZK',
            'price' => (string) $p->price,
            'availability' => $availability,
            'itemCondition' => 'https://schema.org/NewCondition',
        ],
    ];
@endphp

@section('title', $title)
@section('description', Str::limit(trim(($p->subtitle ? $p->subtitle.' ' : '').'Originál Villeroy & Boch za '.Fmt::price($p->price).($pct ? ' místo '.Fmt::price($p->rrp) : '').'.'), 158))
@section('og_type', 'product')
@if ($main)
  @section('og_image', Fmt::img($main['url'], 1200))
@endif

@push('jsonld')
<script type="application/ld+json">{!! json_encode($jsonLd, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) !!}</script>
@endpush

@section('content')
<div class="wrap">
  <nav class="crumbs" aria-label="Drobečková navigace">
    <a href="{{ route('home') }}">Domů</a><svg class="ic" aria-hidden="true"><use href="#i-chev-r"/></svg>
    @if ($category)<a href="{{ route('shop.category', $category) }}">{{ $category->name }}</a><svg class="ic" aria-hidden="true"><use href="#i-chev-r"/></svg>@endif
    @if ($collection)<a href="{{ route('shop.collection', $collection) }}">{{ $collection->name }}</a><svg class="ic" aria-hidden="true"><use href="#i-chev-r"/></svg>@endif
    <span aria-current="page">{{ $p->name }}</span>
  </nav>

  <div class="pdp">
    <div class="gallery{{ count($images) < 2 ? ' is-single' : '' }}">
      @if (count($images) > 1)
        <div class="thumbs" role="group" aria-label="Fotografie produktu">
          @foreach ($images as $i => $img)
            <a class="thumb{{ $i === 0 ? ' is-active' : '' }}" href="{{ Fmt::img($img['url'], 1400) }}" aria-label="Fotografie {{ $i + 1 }} z {{ count($images) }}" @if ($i === 0) aria-current="true" @endif
               data-thumb data-i="{{ $i }}" data-src="{{ Fmt::img($img['url'], 1000) }}" data-srcset="{{ Fmt::srcset($img['url'], [600, 1000, 1400]) }}" data-alt="{{ $img['alt'] ?? '' }}" data-focus="{{ $img['focus'] ?? '' }}"><img src="{{ Fmt::img($img['url'], 200) }}" width="200" height="250" alt="" @if (! empty($img['focus'])) style="object-position: {{ $img['focus'] }}" @endif></a>
          @endforeach
        </div>
      @endif
      <div class="gallery-main">
        @if ($p->badgeLabel())<span class="card-tag{{ $p->badge === 'posledni-kusy' ? '' : ' dark' }}">{{ $p->badgeLabel() }}</span>@endif
        @if ($main)
          <img id="galleryMain" src="{{ Fmt::img($main['url'], 1000) }}" srcset="{{ Fmt::srcset($main['url'], [600, 1000, 1400]) }}" sizes="(max-width: 1024px) 92vw, 52vw" width="1000" height="1250" fetchpriority="high" alt="{{ $main['alt'] ?? $p->name }}" @if (! empty($main['focus'])) style="object-position: {{ $main['focus'] }}" @endif>
        @endif
        @if (count($images) > 1)
          <span class="gallery-count" id="galleryCount" aria-hidden="true">01 / {{ str_pad((string) count($images), 2, '0', STR_PAD_LEFT) }}</span>
        @endif
      </div>
    </div>

    <div class="pinfo">
      <p class="label">{{ $collection ? $collection->name.' · ' : '' }}Villeroy &amp; Boch</p>
      <h1>{{ $p->name }}</h1>
      @if ($p->subtitle)<p class="sub">{{ $p->subtitle }}</p>@endif

      <form class="buy-form" id="buyForm" method="post" action="{{ route('cart.add') }}" data-add-form data-pdp>
        @csrf
        <input type="hidden" name="product_id" value="{{ $p->id }}">

        <div class="pprice">
          <div class="row">
            <span class="now" data-price-now>{{ Fmt::price($currentPrice) }}</span>
            <span class="price-save" data-price-pct @if (! $pct) hidden @endif>−{{ $pct }}&nbsp;%</span>
          </div>
          <div class="row" data-price-rrp-row @if (! $pct) hidden @endif>
            <span class="rrp">Doporučená cena <s data-price-rrp>{{ Fmt::price($currentRrp) }}</s></span>
            <span class="saved" data-price-saved>Ušetříte {{ Fmt::price(max(0, ($currentRrp ?? 0) - $currentPrice)) }}</span>
          </div>
          <p class="stock stock-{{ $stockState }}"><i aria-hidden="true"></i> {{ $stockText }}</p>
        </div>

        @if (count($options) > 1)
          <fieldset class="opt">
            <legend class="opt-label">{{ $optLabel }} <span data-opt-current>{{ $current['label'] }}</span></legend>
            <div class="opts">
              @foreach ($options as $o)
                @php
                    $units = max(1, (int) ($o['units'] ?? 1));
                    $oRrp = $o['rrp'] ?? ($p->rrp ? $p->rrp * $units : null);
                @endphp
                <label class="opt-btn">
                  <input class="opt-input" type="radio" name="option" value="{{ $o['code'] }}" @checked((string) $o['code'] === (string) $current['code'])
                         data-price="{{ $o['price'] }}" data-rrp="{{ $oRrp }}" data-label="{{ $o['label'] }}">
                  <span>{{ $o['label'] }}</span>
                  <small>{{ $units > 1 ? Fmt::price(round($o['price'] / $units)).' / ks' : Fmt::price($o['price']) }}</small>
                </label>
              @endforeach
            </div>
          </fieldset>
        @elseif (count($options) === 1)
          <input type="hidden" name="option" value="{{ $options[0]['code'] }}">
        @endif

        <label class="opt-label qty-label" for="qty">Množství</label>
        <div class="buy">
          <div class="qty">
            <button type="button" aria-label="Snížit množství" data-qty-step="-1"><svg class="ic" aria-hidden="true"><use href="#i-minus"/></svg></button>
            <input id="qty" name="qty" type="number" min="1" max="{{ \App\Services\Cart::MAX_QTY }}" value="1" inputmode="numeric">
            <button type="button" aria-label="Zvýšit množství" data-qty-step="1"><svg class="ic" aria-hidden="true"><use href="#i-plus"/></svg></button>
          </div>
          <button class="btn btn-primary" type="submit"><svg class="ic" aria-hidden="true"><use href="#i-bag"/></svg> Přidat do košíku</button>
          <button class="wish-btn{{ $isFavorite ? ' is-on' : '' }}" type="submit" form="wishForm" aria-pressed="{{ $isFavorite ? 'true' : 'false' }}"><svg class="ic" aria-hidden="true"><use href="#i-heart"/></svg><span class="wish-text">Uložit do oblíbených</span></button>
        </div>
      </form>
      <form id="wishForm" method="post" action="{{ route('favorites.toggle', $p) }}" data-wish-form data-wish-target=".wish-btn">@csrf</form>

      <ul class="trust">
        <li><svg class="ic" aria-hidden="true"><use href="#i-shield"/></svg><span><b>Originál V&amp;B</b>Nové, nepoužité</span></li>
        <li><svg class="ic" aria-hidden="true"><use href="#i-box"/></svg><span><b>Bezpečné balení</b>Pojištěná přeprava</span></li>
        <li><svg class="ic" aria-hidden="true"><use href="#i-return"/></svg><span><b>Vrácení do 14 dní</b>Bez udání důvodu</span></li>
      </ul>

      <div class="acc">
        <details open>
          <summary>Popis <svg class="ic" aria-hidden="true"><use href="#i-plus"/></svg></summary>
          <div class="acc-body">
            @foreach (preg_split("/\n\s*\n/", trim((string) $p->description)) as $para)
              @if (trim($para) !== '')<p>{{ trim($para) }}</p>@endif
            @endforeach
          </div>
        </details>
        @if (! empty($p->set_contents))
          <details open>
            <summary>Obsah sady <svg class="ic" aria-hidden="true"><use href="#i-plus"/></svg></summary>
            <div class="acc-body">
              <ul class="set-list">
                @foreach ($p->set_contents as $line)
                  <li><svg class="ic" aria-hidden="true"><use href="#i-check"/></svg>{{ $line }}</li>
                @endforeach
              </ul>
            </div>
          </details>
        @endif
        <details>
          <summary>Parametry <svg class="ic" aria-hidden="true"><use href="#i-plus"/></svg></summary>
          <div class="acc-body">
            <table class="spec">
              @if ($collection)<tr><th scope="row">Kolekce</th><td>{{ $collection->name }}</td></tr>@endif
              <tr><th scope="row">Materiál</th><td>{{ Str::ucfirst($p->material) }}</td></tr>
              @if ($p->dimensions)<tr><th scope="row">Rozměry</th><td>{{ $p->dimensions }}</td></tr>@endif
              <tr><th scope="row">Myčka nádobí</th><td>@if ($p->dishwasher)<span class="yes"><svg class="ic" aria-hidden="true"><use href="#i-check"/></svg>Ano</span>@else Ne @endif</td></tr>
              <tr><th scope="row">Mikrovlnná trouba</th><td>@if ($p->microwave)<span class="yes"><svg class="ic" aria-hidden="true"><use href="#i-check"/></svg>Ano</span>@else Ne @endif</td></tr>
              <tr><th scope="row">Výrobce</th><td>Villeroy &amp; Boch</td></tr>
              <tr><th scope="row">Stav</th><td>Nové, nepoužité</td></tr>
            </table>
          </div>
        </details>
        <details>
          <summary>Péče <svg class="ic" aria-hidden="true"><use href="#i-plus"/></svg></summary>
          <div class="acc-body">
            @if ($p->dishwasher && $p->microwave)
              <p>Snese myčku i&nbsp;mikrovlnnou troubu. Doporučujeme šetrný program do 65&nbsp;°C a&nbsp;kusy nestohovat za horka.</p>
            @elseif ($p->dishwasher && str_contains((string) $p->material, 'dřev'))
              <p>Porcelánovou část můžete mýt v&nbsp;myčce, dřevěný úchyt před mytím odšroubujte a&nbsp;otřete vlhkým hadříkem. Do mikrovlnné trouby nepatří.</p>
            @elseif ($p->dishwasher)
              <p>Do myčky ano, ideálně šetrným programem do 55&nbsp;°C. Do mikrovlnné trouby nepatří{{ str_contains($p->name.' '.$collection?->name, 'Gold') || str_contains((string) $p->subtitle, 'zlat') ? ' – kov ve zlatém dekoru by jiskřil' : '' }}.</p>
            @else
              <p>Myjte ručně vlažnou vodou s&nbsp;jemným prostředkem. Do myčky ani do mikrovlnné trouby nepatří.</p>
            @endif
          </div>
        </details>
        <details>
          <summary>Doprava a vrácení <svg class="ic" aria-hidden="true"><use href="#i-plus"/></svg></summary>
          <div class="acc-body">
            <p>Skladové kusy odesíláme do 24&nbsp;hodin. {{ $deliveryText }}; od {{ Fmt::price(config('shop.free_shipping_from')) }} je doprava zdarma. Platíte až po potvrzení objednávky, převodem nebo na dobírku.</p>
            <p>Zboží můžete do 14&nbsp;dní vrátit bez udání důvodu. <a class="text-link" href="{{ route('pages.returns') }}">Reklamace a vrácení</a></p>
          </div>
        </details>
      </div>
    </div>
  </div>

  @if ($collection && $collection->description)
    <section class="pdp-story{{ $storyImage ? '' : ' no-media' }}" aria-labelledby="h-story">
      <div>
        <div class="sec-top"><span class="sec-num">—</span><span class="label">O kolekci</span><span class="rule"></span></div>
        <h2 class="h2" id="h-story">Kolekce {{ $collection->name }}</h2>
        <div class="cols">
          <p class="dropcap">{{ $collection->description }}</p>
          <p>Všechny kusy kolekce {{ $collection->name }} v&nbsp;našem obchodě jsou nové, nepoužité a&nbsp;s&nbsp;originálním označením výrobce. Sadu můžete doplňovat postupně – jednotlivé talíře, misky i&nbsp;šálky ladí k&nbsp;sobě.</p>
        </div>
        <a class="link-arrow" href="{{ route('shop.collection', $collection) }}">Celá kolekce {{ $collection->name }} <svg class="ic" aria-hidden="true"><use href="#i-arrow"/></svg></a>
      </div>
      @if ($storyImage)
        <div class="ps-img"><img src="{{ Fmt::img($storyImage['url'], 1000) }}" srcset="{{ Fmt::srcset($storyImage['url'], [600, 1000, 1400]) }}" sizes="(max-width: 1024px) 92vw, 45vw" width="1200" height="800" loading="lazy" alt="{{ $storyImage['alt'] }}"></div>
      @endif
    </section>
  @endif

  @if ($related->isNotEmpty())
    <section class="related" aria-labelledby="h-rel">
      <div class="sec-top"><span class="sec-num">+</span><span class="label">Doplňte sadu</span><span class="rule"></span></div>
      <div class="sec-head">
        <h2 class="h2" id="h-rel">Ke stolu se <em>hodí také</em></h2>
      </div>
      <div class="grid-products">
        @foreach ($related as $r)
          <x-product-card :product="$r" />
        @endforeach
      </div>
      @if ($category)
        <div class="more-row"><a class="btn btn-ghost" href="{{ route('shop.category', $category) }}">Zobrazit kategorii {{ $category->name }}</a></div>
      @endif
    </section>
  @endif
</div>
@endsection
