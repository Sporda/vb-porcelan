@extends('layouts.shop')

@section('title', 'Oblíbené')
@section('description', 'Porcelán, který jste si uložili do oblíbených.')

@section('content')
<div class="wrap">
  <nav class="crumbs" aria-label="Drobečková navigace">
    <a href="{{ route('home') }}">Domů</a><svg class="ic" aria-hidden="true"><use href="#i-chev-r"/></svg>
    <span aria-current="page">Oblíbené</span>
  </nav>
  <header class="page-head">
    <p class="label">Uloženo na později</p>
    <h1>Oblíbené <em>kusy</em>@if ($products->isNotEmpty())<sup>{{ $products->count() }}</sup>@endif</h1>
  </header>
  <div class="page-body">
    @if ($products->isEmpty())
      <div class="empty">
        <p class="empty-t">Zatím tu nic není</p>
        <p>Srdíčkem u&nbsp;produktu si kus uložíte na později. Seznam zůstane v&nbsp;tomto prohlížeči.</p>
        <p class="empty-links"><a class="btn btn-primary" href="{{ route('shop.index') }}">Prohlédnout porcelán</a></p>
      </div>
    @else
      <div class="grid-products">
        @foreach ($products as $p)
          <x-product-card :product="$p" />
        @endforeach
      </div>
    @endif
  </div>
</div>
@endsection
