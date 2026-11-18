@extends('layouts.shop')
@php use App\View\Fmt; @endphp

@section('title', 'Kolekce Villeroy & Boch')
@section('description', 'Všechny kolekce porcelánu Villeroy & Boch, které máme skladem: Royal, Afina, NewMoon, Anmut Gold, Old Luxembourg a další.')

@section('content')
<div class="wrap">
  <nav class="crumbs" aria-label="Drobečková navigace">
    <a href="{{ route('home') }}">Domů</a><svg class="ic" aria-hidden="true"><use href="#i-chev-r"/></svg>
    <span aria-current="page">Kolekce</span>
  </nav>
  <header class="cat-hero">
    <div>
      <p class="label">Villeroy &amp; Boch</p>
      <h1>Kolekce <em>porcelánu</em><sup>{{ $collections->count() }}</sup></h1>
    </div>
    <p>Každá kolekce má vlastní tvar a&nbsp;dekor, kusy v&nbsp;ní k&nbsp;sobě ladí. Sadu tak můžete skládat postupně.</p>
  </header>
  <div class="coll-index page-body">
    @foreach ($collections as $c)
      <a class="coll-card" href="{{ route('shop.collection', $c) }}">
        <div class="coll-media">
          @if ($c->image_url)<img src="{{ Fmt::img($c->image_url, 700) }}" srcset="{{ Fmt::srcset($c->image_url, [400, 700, 1000]) }}" sizes="(max-width: 760px) 92vw, (max-width: 1180px) 45vw, 30vw" width="700" height="875" loading="{{ $loop->index < 3 ? 'eager' : 'lazy' }}" alt="Porcelán z kolekce {{ $c->name }}">@endif
        </div>
        <div class="coll-body">
          <h2>{{ $c->name }}</h2>
          <p>{{ $c->description }}</p>
          <div class="coll-meta"><span class="from">od <b>{{ Fmt::price($c->products_min_price) }}</b> · {{ Fmt::count($c->products_count, 'kus', 'kusy', 'kusů') }}</span><span class="link-arrow">Zobrazit <svg class="ic" aria-hidden="true"><use href="#i-arrow"/></svg></span></div>
        </div>
      </a>
    @endforeach
  </div>
</div>
@endsection
