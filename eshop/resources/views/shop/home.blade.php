@extends('layouts.shop')
@php
    use App\View\Fmt;
    $px = fn (int $id, int $w) => "https://images.pexels.com/photos/{$id}/pexels-photo-{$id}.jpeg?auto=compress&cs=tinysrgb&w={$w}";
    $heroProduct = \App\Models\Product::where('slug', 'chateau-blanc-jidelni-sada-18-dilu')->first();
    $rows = $categories->map(fn ($c) => [
        'name' => $c->name, 'url' => route('shop.category', $c), 'count' => $c->products_count, 'image' => $c->image_url, 'sale' => false,
    ]);
    if ($stats['sale'] > 0) {
        $rows->push(['name' => 'Výprodej', 'url' => route('shop.sale'), 'count' => $stats['sale'], 'image' => $saleImage, 'sale' => true]);
    }
    $collLabels = ['Kolekce měsíce', 'Na čaj a kávu', 'Na každý den'];
@endphp

@section('description', 'Originální porcelán Villeroy & Boch přímo z výroby: talíře, šálky, misky i jídelní sety až o '.$stats['max_discount'].' % pod doporučenou cenou. Doprava zdarma od '.Fmt::price(config('shop.free_shipping_from')).'.')

@section('content')

<section class="hero" data-enter aria-labelledby="hero-h">
  <div class="wrap hero-grid">
    <div class="hero-copy">
      <p class="label">Přímo od výrobce</p>
      <h1 id="hero-h">Porcelán Villeroy &amp; Boch, <em>který se dědí</em></h1>
      <p class="lead">Nové, nepoužité talíře, šálky i&nbsp;celé jídelní sety kupujeme bez velkoobchodu a&nbsp;distributora. Ušetřenou marži necháváme vám.</p>
      <div class="hero-cta">
        <a class="btn btn-primary" href="{{ route('shop.index') }}">Prohlédnout porcelán <svg class="ic" aria-hidden="true"><use href="#i-arrow"/></svg></a>
        <a class="link-arrow" href="#pribeh">Proč tak levně <svg class="ic" aria-hidden="true"><use href="#i-arrow"/></svg></a>
      </div>
      <dl class="hero-stats">
        <div class="stat"><dt class="sr-only">Sleva</dt><dd><b>až −{{ $stats['max_discount'] }}&nbsp;%</b><span>pod doporučenou cenou výrobce</span></dd></div>
        <div class="stat"><dt class="sr-only">Odeslání</dt><dd><b>24&nbsp;h</b><span>odeslání skladových kusů</span></dd></div>
        <div class="stat"><dt class="sr-only">Kolekce</dt><dd><b>{{ $collectionsCount }}</b><span>{{ Fmt::plural($collectionsCount, 'kolekce', 'kolekce', 'kolekcí') }} právě skladem</span></dd></div>
      </dl>
    </div>
    <div class="hero-media">
      <a class="hero-main" href="{{ $heroProduct ? route('shop.product', $heroProduct) : route('shop.index') }}" aria-label="{{ $heroProduct ? 'Château Blanc – '.$heroProduct->name : 'Všechny produkty' }}"><img src="{{ $px(18977900, 1000) }}" srcset="{{ $px(18977900, 700) }} 700w, {{ $px(18977900, 1000) }} 1000w, {{ $px(18977900, 1400) }} 1400w" sizes="(max-width: 1024px) 90vw, 50vw" width="1400" height="1867" fetchpriority="high" alt="Naskládané bílé talíře s reliéfním okrajem, hluboký talíř a šálek na podšálku na lněném ubrusu"></a>
      <div class="hero-detail"><img src="{{ $px(34788168, 500) }}" srcset="{{ $px(34788168, 360) }} 360w, {{ $px(34788168, 700) }} 700w" sizes="(max-width: 760px) 40vw, 20vw" width="700" height="1050" alt="Porcelánový šálek s květinovým dekorem a zlatým okrajem na podšálku"></div>
    </div>
  </div>
</section>

<section class="values" aria-label="Proč nakupovat u nás">
  <ul class="wrap values-in">
    <li><svg class="ic" aria-hidden="true"><use href="#i-shield"/></svg><div><strong>Originál Villeroy &amp; Boch</strong><span>Nové zboží s&nbsp;dokladem o&nbsp;koupi</span></div></li>
    <li><svg class="ic" aria-hidden="true"><use href="#i-box"/></svg><div><strong>Bezpečně zabaleno</strong><span>Každý kus balíme ručně a&nbsp;pojištěně</span></div></li>
    <li><svg class="ic" aria-hidden="true"><use href="#i-return"/></svg><div><strong>Vrácení do 14 dní</strong><span>Bez udání důvodu</span></div></li>
  </ul>
</section>

<section class="sec" aria-labelledby="h-kat">
  <div class="wrap">
    <div class="sec-top"><span class="sec-num">01</span><span class="label">Kategorie</span><span class="rule"></span></div>
    <div class="sec-head">
      <h2 class="h2" id="h-kat">Porcelán podle <em>kategorií</em></h2>
      <p class="sec-aside">{{ $categories->count() }} oddělení a&nbsp;{{ Fmt::count($stats['available'], 'výrobek', 'výrobky', 'výrobků') }} skladem. Od každodenních hrnků po sváteční jídelní sety.</p>
    </div>
    <div class="cat-index">
      <ol class="cat-list" id="catList">
        @foreach ($rows as $i => $r)
          <li><a class="cat-row{{ $i === 0 ? ' is-active' : '' }}{{ $r['sale'] ? ' sale' : '' }}" href="{{ $r['url'] }}" data-i="{{ $i }}">
            <span class="num">{{ str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT) }}</span>
            @if ($r['image'])<img class="cat-thumb" src="{{ Fmt::img($r['image'], 160) }}" width="56" height="70" loading="lazy" alt="">@endif
            <span class="name">{{ $r['name'] }}</span><span class="count">{{ Fmt::count($r['count'], 'produkt', 'produkty', 'produktů') }}</span>
            <svg class="ic" aria-hidden="true"><use href="#i-arrow"/></svg></a></li>
        @endforeach
      </ol>
      <div class="cat-preview" id="catPreview" aria-hidden="true">
        @foreach ($rows as $i => $r)
          @if ($r['image'])
            <img class="{{ $i === 0 ? 'is-active' : '' }}" src="{{ Fmt::img($r['image'], 900) }}" srcset="{{ Fmt::srcset($r['image'], [600, 900]) }}" sizes="(max-width: 760px) 92vw, 40vw" width="900" height="1125" loading="lazy" alt="" data-i="{{ $i }}">
          @endif
        @endforeach
      </div>
    </div>
  </div>
</section>

<section class="sec collections" aria-labelledby="h-kol">
  <div class="wrap">
    <div class="sec-top"><span class="sec-num">02</span><span class="label">Kolekce</span><span class="rule"></span></div>
    <div class="sec-head">
      <h2 class="h2" id="h-kol">Tři kolekce <em>porcelánu</em></h2>
      <a class="link-arrow" href="{{ route('shop.collections') }}">Všechny kolekce <svg class="ic" aria-hidden="true"><use href="#i-arrow"/></svg></a>
    </div>
    <div class="coll-grid">
      @foreach ($featuredCollections as $i => $c)
        <a class="coll" href="{{ route('shop.collection', $c) }}">
          <div class="coll-media"><img src="{{ Fmt::img($c->image_url, $i === 0 ? 1200 : 800) }}" srcset="{{ Fmt::srcset($c->image_url, [600, 900, 1200, 1600]) }}" sizes="(max-width: 760px) 100vw, {{ $i === 0 ? '60vw' : '45vw' }}" width="{{ $i === 0 ? 1200 : 800 }}" height="{{ $i === 0 ? 1500 : 1067 }}" loading="lazy" alt="Porcelán z kolekce {{ $c->name }}"></div>
          <div class="coll-body">
            <p class="label">{{ $collLabels[$i] ?? 'Kolekce' }}</p>
            <h3>{{ $c->name }}</h3>
            <p>{{ $c->description }}</p>
            <div class="coll-meta"><span class="from">od <b>{{ Fmt::price($c->products_min_price) }}</b> · {{ Fmt::count($c->products_count, 'kus', 'kusy', 'kusů') }}</span>@if ($i === 0)<span class="link-arrow">Zobrazit kolekci <svg class="ic" aria-hidden="true"><use href="#i-arrow"/></svg></span>@endif</div>
          </div>
        </a>
      @endforeach
    </div>
  </div>
</section>

<section class="sec" id="vyber" aria-labelledby="h-vyber">
  <div class="wrap">
    <div class="sec-top"><span class="sec-num">03</span><span class="label">Výběr</span><span class="rule"></span></div>
    <div class="sec-head">
      <h2 class="h2" id="h-vyber">Nejprodávanější <em>porcelán</em></h2>
      <div class="tabs" role="tablist" aria-label="Výběr produktů">
        @foreach (\App\Http\Controllers\Shop\HomeController::PICKS as $key => $label)
          <a class="tab" role="tab" id="tab-{{ $key }}" href="{{ route('home', ['vyber' => $key]) }}#vyber" aria-selected="{{ $pick === $key ? 'true' : 'false' }}" aria-controls="panel-{{ $key }}" data-pick="{{ $key }}">{{ $label }}</a>
        @endforeach
      </div>
    </div>
    @foreach ($picks as $key => $items)
      <div class="grid-products home-grid" id="panel-{{ $key }}" role="tabpanel" aria-labelledby="tab-{{ $key }}" data-pick-panel="{{ $key }}" @if ($pick !== $key) hidden @endif>
        @foreach ($items as $p)
          <x-product-card :product="$p" />
        @endforeach
      </div>
    @endforeach
    <div class="more-row"><a class="btn btn-ghost" href="{{ route('shop.index') }}">Zobrazit všech {{ Fmt::count($stats['products'], 'produkt', 'produkty', 'produktů') }}</a></div>
  </div>
</section>

<section class="sec story" id="pribeh" aria-labelledby="h-pribeh">
  <div class="wrap">
    <div class="sec-top"><span class="sec-num">04</span><span class="label">Přímo z&nbsp;výroby</span><span class="rule"></span></div>
    <div class="story-grid">
      <div class="story-media">
        <div class="m1"><img src="{{ $px(8066048, 1000) }}" srcset="{{ $px(8066048, 600) }} 600w, {{ $px(8066048, 1000) }} 1000w" sizes="(max-width: 1024px) 80vw, 35vw" width="1000" height="1500" loading="lazy" alt="Regály se sádrovými formami na výrobu porcelánu v dílně"></div>
        <div class="m2"><img src="{{ $px(7191403, 700) }}" srcset="{{ $px(7191403, 400) }} 400w, {{ $px(7191403, 700) }} 700w" sizes="(max-width: 1024px) 45vw, 20vw" width="700" height="1050" loading="lazy" alt="Naskládané formy na misky v regálu porcelánové dílny"></div>
      </div>
      <div class="story-copy">
        <h2 class="h2" id="h-pribeh">Proč je u&nbsp;nás porcelán <em>levnější</em></h2>
        <p class="intro">Žádné kouzlo, jen kratší cesta. Odebíráme větší objemy a&nbsp;vynecháváme velkoobchod, distributora i&nbsp;drahé kamenné prodejny.</p>
        <ol class="steps">
          <li class="step"><span class="n">01</span><div><p class="step-t">Nakupujeme u&nbsp;výrobce</p><p>Nové zboží z&nbsp;aktuální i&nbsp;končící produkce, vždy s&nbsp;originálním označením.</p></div></li>
          <li class="step"><span class="n">02</span><div><p class="step-t">Kontrolujeme každý kus</p><p>Talíř i&nbsp;šálek projdou před odesláním naší kontrolou kvality.</p></div></li>
          <li class="step"><span class="n">03</span><div><p class="step-t">Balíme jako křehké sklo</p><p>Pevné krabice, recyklovatelné výplně a&nbsp;pojištěná přeprava.</p></div></li>
        </ol>
        @if ($example && $example->rrp)
          <div class="compare" aria-label="Srovnání ceny">
            <p class="label">Příklad: {{ $example->collection?->name }}, {{ Str::lower(Str::substr($example->name, 0, 1)).Str::substr($example->name, 1) }}</p>
            <div class="bar"><span>Doporučená cena</span><s class="nums">{{ Fmt::price($example->rrp) }}</s><div class="bar-track"><div class="bar-fill" style="width:100%"></div></div></div>
            <div class="bar ours"><span>U&nbsp;nás</span><b>{{ Fmt::price($example->price) }}</b><div class="bar-track"><div class="bar-fill" style="width:{{ max(5, min(100, round(100 * $example->price / $example->rrp))) }}%"></div></div></div>
          </div>
        @endif
        <p class="story-more"><a class="link-arrow on-dark" href="{{ route('pages.why') }}">Celý postup nákupu <svg class="ic" aria-hidden="true"><use href="#i-arrow"/></svg></a></p>
      </div>
    </div>
  </div>
</section>

<section class="sec newsletter" id="novinky" aria-labelledby="h-nl">
  <div class="wrap nl-grid">
    <div class="nl-media"><img src="{{ $px(20075994, 1000) }}" srcset="{{ $px(20075994, 600) }} 600w, {{ $px(20075994, 1000) }} 1000w" sizes="(max-width: 1024px) 92vw, 42vw" width="1000" height="667" loading="lazy" alt="Tři porcelánové šálky s podšálky: reliéfní bílý, se zlatou bordurou a se zlatou linkou"></div>
    <div class="nl-body">
      <div class="sec-top"><span class="sec-num">05</span><span class="label">Novinky e-mailem</span><span class="rule"></span></div>
      <h2 class="h2" id="h-nl">Nové zásilky porcelánu <em>jako první</em></h2>
      <p>Jednou za čtrnáct dní pošleme, co nového dorazilo z&nbsp;výroby – dřív, než to zveřejníme na webu.</p>
      <form class="nl-form" method="post" action="{{ route('newsletter.store') }}" novalidate>
        @csrf
        <label for="nl-email">Váš e-mail</label>
        <div class="nl-field">
          <input id="nl-email" name="email" type="email" placeholder="vas@email.cz" autocomplete="email" required value="{{ old('email') }}" @error('email', 'newsletter') aria-invalid="true" aria-describedby="nl-msg" @enderror>
          <button class="btn btn-primary" type="submit">Odebírat novinky</button>
        </div>
        @error('email', 'newsletter')
          <small class="field-error" id="nl-msg" role="alert">{{ $message }}</small>
        @else
          <small role="status">{{ session('newsletter', 'Žádný spam. Odhlásit se můžete jedním klikem.') }}</small>
        @enderror
      </form>
    </div>
  </div>
</section>

@endsection
