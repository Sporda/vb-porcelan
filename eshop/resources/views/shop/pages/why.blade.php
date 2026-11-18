@extends('shop.pages.layout')
@php
    use App\View\Fmt;
    $examples = \App\Models\Product::with('collection')->whereNotNull('rrp')->orderByDesc('is_featured')->orderBy('sort')->take(5)->get();
    $px = fn (int $id, int $w) => "https://images.pexels.com/photos/{$id}/pexels-photo-{$id}.jpeg?auto=compress&cs=tinysrgb&w={$w}";
@endphp

@section('title', 'Proč tak levně')
@section('description', 'Proč stojí originální porcelán Villeroy & Boch u nás v průměru o '.$stats['avg_discount'].' % méně než doporučená cena: kratší cesta od výrobce, větší odběry, končící řady.')
@section('page_label', 'Přímo z výroby')
@section('page_h1')Proč je u&nbsp;nás porcelán <em>levnější</em>@endsection
@section('page_lead', 'Žádné kouzlo, jen kratší cesta. Originální Villeroy & Boch u nás stojí v průměru o '.$stats['avg_discount'].' % méně, než je doporučená cena výrobce.')

@section('page_body')
  <h2>Kupujeme přímo u&nbsp;výrobce</h2>
  <p>Běžný talíř projde cestou z&nbsp;továrny k&nbsp;vám přes velkoobchod, distributora a&nbsp;kamenný obchod. Každý článek si přičte svou marži a&nbsp;doporučená cena s&nbsp;nimi počítá. My nakupujeme přímo od Villeroy &amp; Boch a&nbsp;prodáváme jen online – dva až tři mezičlánky tak odpadají.</p>

  <h2>Odebíráme větší objemy</h2>
  <p>Objednáváme celé palety jedné kolekce, ne jednotlivé krabice. Za větší odběr dostaneme lepší nákupní cenu a&nbsp;rozdíl posíláme dál do vaší ceny.</p>

  <h2>Končící řady a&nbsp;poslední kusy</h2>
  <p>Část nabídky tvoří dekory a&nbsp;tvary, které výrobce mění za novější. Jde o&nbsp;nové, nepoužité zboží s&nbsp;originálním označením, jen už nebude v&nbsp;dalších katalozích. Takové kusy najdete ve <a class="text-link" href="{{ route('shop.sale') }}">výprodeji</a> se slevou od {{ \App\Services\Catalog::SALE_PERCENT }}&nbsp;%.</p>

  <h2>Kolik ušetříte</h2>
  <table class="price-table">
    <thead><tr><th scope="col">Produkt</th><th scope="col">Doporučená cena</th><th scope="col">U&nbsp;nás</th></tr></thead>
    <tbody>
      @foreach ($examples as $e)
        <tr>
          <th scope="row"><a class="text-link" href="{{ route('shop.product', $e) }}">{{ $e->collection?->name }} – {{ $e->name }}</a></th>
          <td><s>{{ Fmt::price($e->rrp) }}</s></td>
          <td><b>{{ Fmt::price($e->price) }}</b> <span class="price-save">−{{ $e->discountPercent() }}&nbsp;%</span></td>
        </tr>
      @endforeach
    </tbody>
  </table>

  <h2>Co nižší cena neznamená</h2>
  <p>Neprodáváme použité zboží, vzorky ani napodobeniny. Každý kus před odesláním zkontrolujeme a&nbsp;zabalíme do pevné krabice. Na zboží se vztahuje běžná zákonná záruka a&nbsp;do 14&nbsp;dní ho můžete vrátit bez udání důvodu.</p>
  <p class="prose-cta"><a class="btn btn-primary" href="{{ route('shop.index') }}">Prohlédnout porcelán <svg class="ic" aria-hidden="true"><use href="#i-arrow"/></svg></a></p>
@endsection

@section('page_aside')
  <div class="info-photo"><img src="{{ $px(29286719, 700) }}" srcset="{{ $px(29286719, 500) }} 500w, {{ $px(29286719, 900) }} 900w" sizes="(max-width: 1024px) 92vw, 30vw" width="700" height="1050" loading="lazy" alt="Sušicí regál plný talířů v porcelánce"></div>
@endsection
