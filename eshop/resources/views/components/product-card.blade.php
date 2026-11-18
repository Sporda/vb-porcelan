@props(['product', 'level' => 'p', 'eager' => false])
@php
    use App\View\Fmt;
    $img = $product->mainImage();
    $url = route('shop.product', $product);
    $pct = $product->discountPercent();
    $fav = app(\App\Services\Favorites::class)->has($product->id);
    $badge = $product->badgeLabel();
    // Název produktu v kartě je jako v návrhu odstavec (p), aby se nemíchal do škály nadpisů stránky.
    $level = in_array($level, ['p', 'h2', 'h3', 'h4'], true) ? $level : 'p';
@endphp
<article class="card" data-product="{{ $product->id }}">
  <div class="card-figure">
    <a class="card-media" href="{{ $url }}" tabindex="-1" aria-hidden="true">
      @if ($img)
        {{-- rámeček 4:5 s object-fit: cover – na šířku focené fotky potřebují zdroj zhruba 2× širší než karta --}}
        <img src="{{ Fmt::img($img['url'], 600) }}" srcset="{{ Fmt::srcset($img['url'], [400, 600, 900, 1200]) }}" sizes="(max-width: 760px) 100vw, (max-width: 1180px) 60vw, 600px" width="600" height="750" @unless ($eager) loading="lazy" @endunless alt="{{ $img['alt'] ?? $product->name }}" @if (! empty($img['focus'])) style="object-position: {{ $img['focus'] }}" @endif>
      @endif
    </a>
    @if ($badge)
      <span class="card-tag{{ $product->badge === 'posledni-kusy' ? ' dark' : '' }}">{{ $badge }}</span>
    @endif
    <form class="card-form" method="post" action="{{ route('favorites.toggle', $product) }}" data-wish-form>
      @csrf
      <button class="card-wish{{ $fav ? ' is-on' : '' }}" type="submit" aria-pressed="{{ $fav ? 'true' : 'false' }}" aria-label="{{ $fav ? 'Odebrat z oblíbených' : 'Uložit do oblíbených' }}: {{ $product->name }}"><svg class="ic" aria-hidden="true"><use href="#i-heart"/></svg></button>
    </form>
    <form class="card-form" method="post" action="{{ route('cart.add') }}" data-add-form>
      @csrf
      <input type="hidden" name="product_id" value="{{ $product->id }}">
      <button class="card-add" type="submit" aria-label="Přidat do košíku: {{ $product->name }}"><svg class="ic" aria-hidden="true"><use href="#i-bag"/></svg><span>Do košíku</span></button>
    </form>
  </div>
  <div class="card-body">
    @if ($product->collection)
      <p class="card-coll">{{ $product->collection->name }}</p>
    @endif
    <{{ $level }} class="card-title"><a href="{{ $url }}">{{ $product->name }}</a></{{ $level }}>
    <p class="price">
      <span class="price-now">{{ Fmt::price($product->price) }}</span>
      @if ($pct)
        <s class="price-rrp"><span class="sr-only">Doporučená cena </span>{{ Fmt::price($product->rrp) }}</s>
        <span class="price-save">−{{ $pct }}&nbsp;%</span>
      @endif
    </p>
  </div>
</article>
