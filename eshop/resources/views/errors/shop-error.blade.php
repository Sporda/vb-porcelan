{{-- Společná podoba chybových stránek v rozvržení obchodu. Proměnné: $code, $heading, $text. --}}
@extends('layouts.shop')

@section('title', $heading)
@section('body_class', 'is-error')

@section('content')
<div class="wrap">
  <section class="error-page" aria-labelledby="err-h">
    <div>
      <p class="label">Chyba {{ $code }}</p>
      <h1 id="err-h">{{ $heading }}</h1>
      <p class="lead">{{ $text }}</p>
      <div class="hero-cta">
        @if (in_array($code, [419, 429], true) && url()->previous() !== url()->current())
          <a class="btn btn-primary" href="{{ url()->previous() }}">Zkusit to znovu <svg class="ic" aria-hidden="true"><use href="#i-arrow"/></svg></a>
          <a class="link-arrow" href="{{ route('home') }}">Na úvodní stránku</a>
        @else
          <a class="btn btn-primary" href="{{ route('home') }}">Na úvodní stránku <svg class="ic" aria-hidden="true"><use href="#i-arrow"/></svg></a>
          <a class="link-arrow" href="{{ route('shop.index') }}">Všechny produkty <svg class="ic" aria-hidden="true"><use href="#i-arrow"/></svg></a>
        @endif
      </div>
      @if ($code === 404)
        <form class="inline-search" role="search" method="get" action="{{ route('shop.search') }}">
          <label class="sr-only" for="q-404">Hledat v obchodě</label>
          <input id="q-404" name="q" type="search" placeholder="Hledat kolekci nebo kus" maxlength="80">
          <button class="btn btn-ghost" type="submit">Hledat</button>
        </form>
      @endif
    </div>
    <div class="error-media"><img src="https://images.pexels.com/photos/18977899/pexels-photo-18977899.jpeg?auto=compress&cs=tinysrgb&w=900" width="900" height="1200" alt="Stoh talířů s reliéfním okrajem a šálek s podšálkem"></div>
  </section>
</div>
@endsection
