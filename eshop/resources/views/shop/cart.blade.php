@extends('layouts.shop')

@section('title', 'Košík')
@section('description', 'Obsah košíku v e-shopu Bílé zlato – originální porcelán Villeroy & Boch.')

@section('content')
<div class="wrap">
  <nav class="crumbs" aria-label="Drobečková navigace">
    <a href="{{ route('home') }}">Domů</a><svg class="ic" aria-hidden="true"><use href="#i-chev-r"/></svg>
    <span aria-current="page">Košík</span>
  </nav>
  <header class="page-head">
    <p class="label">Nákup · krok 1 ze 2</p>
    <h1>Váš <em>košík</em></h1>
  </header>
  <div id="cartContent" class="page-body" data-cart-content>
    @include('shop.partials.cart-content', ['cart' => $cart])
  </div>
</div>
@endsection
