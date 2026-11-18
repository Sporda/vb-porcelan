@extends('layouts.shop')
@php
    use App\View\Fmt;
    $mode = $page['mode'];
    $total = $products ? $products->total() : 0;
    $filters = $filters ?? null;
    $chips = $chips ?? [];
    $isFirstPage = ! $products || $products->currentPage() === 1;
    $canonical = request()->url();
    if ($mode === 'search' && ($page['query'] ?? '') !== '') {
        $canonical .= '?'.http_build_query(['q' => $page['query']]);
    } elseif ($products && $products->currentPage() > 1) {
        $canonical .= '?strana='.$products->currentPage();
    }
    $description = $page['lead'] ?: 'Originální porcelán Villeroy & Boch se slevou až '.$avgDiscount.' % proti doporučené ceně.';
    $showTile = $mode !== 'search' && $isFirstPage && $total > 6;
    $nextCount = $products && $products->hasMorePages() ? min(\App\Services\ProductListing::PER_PAGE, $total - $products->lastItem()) : 0;
@endphp

@section('title', $page['title'])
@section('description', \Illuminate\Support\Str::limit($description, 158))
@section('canonical', $canonical)
@if (! empty($page['image']))
  @section('og_image', Fmt::img($page['image'], 1200))
@endif

@section('content')
<div class="wrap">
  <nav class="crumbs" aria-label="Drobečková navigace">
    <a href="{{ route('home') }}">Domů</a>
    @foreach ($page['crumbs'] as [$label, $url])
      <svg class="ic" aria-hidden="true"><use href="#i-chev-r"/></svg>
      @if ($url)<a href="{{ $url }}">{{ $label }}</a>@else<span aria-current="page">{{ $label }}</span>@endif
    @endforeach
  </nav>

  <header class="cat-hero">
    <div>
      <p class="label">{{ $page['label'] }}</p>
      <h1>{{ $page['h1'][0] }}@if ($page['h1'][1]) <em>{{ $page['h1'][1] }}</em>@endif @if ($products)<sup>{{ $total }}</sup>@endif</h1>
    </div>
    @if ($page['lead'])
      <p>{{ $page['lead'] }}</p>
    @elseif ($mode === 'search')
      <form class="inline-search" role="search" method="get" action="{{ route('shop.search') }}">
        <label class="sr-only" for="q-page">Hledat v obchodě</label>
        <input id="q-page" name="q" type="search" value="{{ $page['query'] ?? '' }}" placeholder="Kolekce, kus nebo velikost" maxlength="80">
        <button class="btn btn-primary" type="submit">Hledat</button>
      </form>
    @endif
  </header>

  @if ($mode !== 'search')
    <nav class="subcats" aria-label="Kategorie">
      <a class="subcat{{ $mode === 'all' ? ' is-active' : '' }}" href="{{ route('shop.index') }}" @if ($mode === 'all') aria-current="page" @endif>Vše</a>
      @foreach ($categories as $c)
        @php $on = $mode === 'category' && $page['category']->is($c); @endphp
        <a class="subcat{{ $on ? ' is-active' : '' }}" href="{{ route('shop.category', $c) }}" @if ($on) aria-current="page" @endif>{{ $c->name }}</a>
      @endforeach
      <a class="subcat{{ $mode === 'sale' ? ' is-active' : '' }}" href="{{ route('shop.sale') }}" @if ($mode === 'sale') aria-current="page" @endif>Výprodej</a>
    </nav>
  @endif

  @if (! empty($page['empty_query']))
    <div class="empty">
      <p class="empty-t">Napište, co hledáte</p>
      <p>Třeba název kolekce, druh nádobí nebo velikost. Nebo začněte od kategorie:</p>
      <p class="empty-links">
        @foreach ($categories as $c)
          <a class="subcat" href="{{ route('shop.category', $c) }}">{{ $c->name }}</a>
        @endforeach
      </p>
    </div>
  @else
    <div class="shop">
      <aside class="filters" id="filters" aria-label="Filtry">
        <form id="filterForm" method="get" action="{{ request()->url() }}" data-filter-form>
          @foreach ($keep as $k => $v)
            <input type="hidden" name="{{ $k }}" value="{{ $v }}">
          @endforeach
          <div class="filters-head">
            <h2>Filtry</h2>
            <button class="icon-btn" type="button" aria-label="Zavřít filtry" data-close-filters><svg class="ic" aria-hidden="true"><use href="#i-close"/></svg></button>
          </div>
          <div class="filters-scroll">
            @if ($filters['collections']->count() > 1)
              <details class="filter-group" open>
                <summary>Kolekce <svg class="ic" aria-hidden="true"><use href="#i-chev-d"/></svg></summary>
                <div class="filter-body">
                  @foreach ($filters['collections'] as $c)
                    <label class="check"><input type="checkbox" name="kolekce[]" value="{{ $c['slug'] }}" @checked($c['on'])> {{ $c['name'] }} <span class="n">{{ $c['count'] }}</span></label>
                  @endforeach
                </div>
              </details>
            @endif
            @if ($filters['types']->count() > 1)
              <details class="filter-group" open>
                <summary>Typ <svg class="ic" aria-hidden="true"><use href="#i-chev-d"/></svg></summary>
                <div class="filter-body">
                  @foreach ($filters['types'] as $c)
                    <label class="check"><input type="checkbox" name="typ[]" value="{{ $c['slug'] }}" @checked($c['on'])> {{ $c['name'] }} <span class="n">{{ $c['count'] }}</span></label>
                  @endforeach
                </div>
              </details>
            @endif
            @php
                $b = $filters['bounds'];
                $span = max(1, $b['max'] - $b['min']);
                $from = $filters['price_from'] ?? $b['min'];
                $to = $filters['price_to'] ?? $b['max'];
                $left = max(0, min(100, round(100 * ($from - $b['min']) / $span)));
                $right = max(0, min(100, round(100 * ($b['max'] - $to) / $span)));
            @endphp
            <details class="filter-group" open>
              <summary>Cena <svg class="ic" aria-hidden="true"><use href="#i-chev-d"/></svg></summary>
              <div class="filter-body">
                <div class="price-range">
                  <label><span class="sr-only">Cena od (Kč)</span><input type="text" inputmode="numeric" name="cena_od" value="{{ $filters['price_from'] }}" placeholder="{{ Fmt::number($b['min']) }}" autocomplete="off"></label>
                  <span aria-hidden="true">–</span>
                  <label><span class="sr-only">Cena do (Kč)</span><input type="text" inputmode="numeric" name="cena_do" value="{{ $filters['price_to'] }}" placeholder="{{ Fmt::number($b['max']) }}" autocomplete="off"></label>
                </div>
                <div class="range-track" aria-hidden="true"><i style="left:{{ $left }}%;right:{{ $right }}%"></i></div>
                <p class="range-note">Kč · {{ Fmt::number($b['min']) }} až {{ Fmt::number($b['max']) }}</p>
              </div>
            </details>
            <details class="filter-group" open>
              <summary>Dostupnost <svg class="ic" aria-hidden="true"><use href="#i-chev-d"/></svg></summary>
              <div class="filter-body">
                <label class="check"><input type="checkbox" name="skladem" value="1" @checked($filters['in_stock'])> Jen skladem <span class="n">{{ $filters['available_count'] }}</span></label>
              </div>
            </details>
            <details class="filter-group" open>
              <summary>Sleva <svg class="ic" aria-hidden="true"><use href="#i-chev-d"/></svg></summary>
              <div class="filter-body">
                <label class="check"><input type="radio" name="sleva" value="" @checked(! $filters['discount'])> Jakákoli</label>
                @foreach ($filters['discount_counts'] as $d => $n)
                  <label class="check"><input type="radio" name="sleva" value="{{ $d }}" @checked($filters['discount'] === $d)> Od {{ $d }}&nbsp;%{{ $d >= \App\Services\Catalog::SALE_PERCENT ? ' · výprodej' : '' }} <span class="n">{{ $n }}</span></label>
                @endforeach
              </div>
            </details>
          </div>
          <div class="filters-foot"><button class="btn btn-primary" type="submit">Použít filtry</button></div>
        </form>
      </aside>

      <div class="shop-main">
        <div class="toolbar">
          <div class="toolbar-left">
            <button class="filter-btn" type="button" data-open-filters aria-controls="filters" aria-expanded="false"><svg class="ic" aria-hidden="true"><use href="#i-sliders"/></svg> Filtry{{ count($chips) ? ' ('.count($chips).')' : '' }}</button>
            @foreach ($chips as $chip)
              <span class="chip">{{ $chip['label'] }} <a href="{{ $chip['url'] }}" aria-label="Odebrat filtr {{ $chip['label'] }}"><svg class="ic" aria-hidden="true"><use href="#i-close"/></svg></a></span>
            @endforeach
            @if (count($chips) > 1)
              <a class="clear-all" href="{{ $clearUrl }}">Vymazat vše</a>
            @endif
          </div>
          <label class="sort"><span>Řadit podle</span>
            <select name="razeni" form="filterForm" aria-label="Řazení" data-autosubmit>
              @foreach ($sorts as $k => $label)
                <option value="{{ $k }}" @selected($sort === $k)>{{ $label }}</option>
              @endforeach
            </select>
            <noscript><button class="btn btn-ghost btn-sm" type="submit" form="filterForm">Seřadit</button></noscript>
          </label>
        </div>

        @if ($total === 0)
          <div class="empty">
            @if ($mode === 'search')
              <p class="empty-t">Pro „{{ $page['query'] }}“ jsme nic nenašli</p>
              <p>Zkuste kratší slovo nebo název kolekce – třeba Royal, Anmut nebo NewMoon. Hledáme v názvech produktů, kolekcí i kategorií.</p>
            @else
              <p class="empty-t">Těmto filtrům nic neodpovídá</p>
              <p>Zkuste zrušit některý filtr nebo rozšířit cenové rozpětí.</p>
            @endif
            <p class="empty-links">
              @if (count($chips))<a class="btn btn-ghost" href="{{ $clearUrl }}">Zrušit filtry</a>@endif
              <a class="btn btn-primary" href="{{ route('shop.index') }}">Všechny produkty</a>
            </p>
          </div>
        @else
          <div class="grid-products grid-shop" id="shopGrid">
            @foreach ($products as $i => $p)
              <x-product-card :product="$p" :eager="$i < 3 && $isFirstPage" />
              @if ($showTile && $i === 5)
                <aside class="edit-tile" aria-label="Proč tak levně">
                  <div>
                    <p class="label">Proč tak levně?</p>
                    <p class="big">Kupujeme přímo od výrobce. <em>Bez mezičlánků a&nbsp;přirážek.</em></p>
                  </div>
                  <div class="edit-side">
                    <p class="small">Proto u&nbsp;nás originální Villeroy &amp; Boch stojí v&nbsp;průměru o&nbsp;{{ $avgDiscount }}&nbsp;% méně, než je doporučená cena.</p>
                    <a class="link-arrow" href="{{ route('pages.why') }}">Jak to funguje <svg class="ic" aria-hidden="true"><use href="#i-arrow"/></svg></a>
                  </div>
                </aside>
              @endif
            @endforeach
          </div>

          <div class="loadmore" data-loadmore>
            <p data-shown>Zobrazeno {{ $products->lastItem() }} z&nbsp;{{ Fmt::count($total, 'produktu', 'produktů', 'produktů') }}</p>
            <div class="progress" aria-hidden="true"><span data-progress style="width:{{ round(100 * $products->lastItem() / max(1, $total)) }}%"></span></div>
            @if ($products->hasMorePages())
              <a class="btn btn-ghost" href="{{ $products->nextPageUrl() }}" data-load-more>Načíst {{ $nextCount === 1 ? 'další' : 'dalších' }} {{ $nextCount }}</a>
            @endif
          </div>
          {{ $products->onEachSide(1)->links('vendor.pagination.shop') }}
        @endif
      </div>
    </div>
  @endif
</div>
@endsection
