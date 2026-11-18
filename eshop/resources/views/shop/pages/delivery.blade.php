@extends('shop.pages.layout')
@php
    use App\View\Fmt;
    $free = Fmt::price(config('shop.free_shipping_from'));
@endphp

@section('title', 'Doprava a platba')
@section('description', 'Zásilkovna, PPL nebo osobní odběr v Praze. Doprava zdarma od '.$free.'. Platíte až po potvrzení objednávky – převodem nebo na dobírku.')
@section('page_label', 'Nákup')
@section('page_h1')Doprava <em>a&nbsp;platba</em>@endsection
@section('page_lead', 'Od '.$free.' posíláme zdarma. Platíte až ve chvíli, kdy vám dostupnost a konečnou cenu potvrdíme e-mailem.')

@section('page_body')
  <h2>Způsoby doručení</h2>
  <table class="price-table">
    <thead><tr><th scope="col">Doprava</th><th scope="col">Doručení</th><th scope="col">Cena</th></tr></thead>
    <tbody>
      @foreach ($delivery as $m)
        <tr>
          <th scope="row">{{ $m['label'] }}</th>
          <td>{{ $m['eta'] }}</td>
          <td><b>{{ $m['price'] ? Fmt::price($m['price']) : 'zdarma' }}</b></td>
        </tr>
      @endforeach
    </tbody>
  </table>
  <p>Při nákupu zboží od {{ $free }} je doprava zdarma u&nbsp;všech způsobů. Doba doručení platí od odeslání; skladové kusy odesíláme do 24&nbsp;hodin od potvrzení platby.</p>

  <h2>Jak platíte</h2>
  <p>V&nbsp;pokladně nic neplatíte – košík odešlete jako nezávaznou poptávku. Po ověření dostupnosti pošleme potvrzení s&nbsp;konečnou cenou a&nbsp;vyberete si:</p>
  <ul>
    <li><b>Bankovním převodem</b> – číslo účtu a&nbsp;variabilní symbol najdete v&nbsp;potvrzení. Zboží odešleme po připsání platby.</li>
    <li><b>Na dobírku</b> – zaplatíte dopravci při převzetí. Případný poplatek za dobírku uvedeme v&nbsp;potvrzení.</li>
  </ul>

  <h2>Balení</h2>
  <p>Porcelán balíme ručně do pevných krabic s&nbsp;recyklovatelnou výplní a&nbsp;zásilky posíláme pojištěné. Pokud zásilka dorazí poškozená, sepište s&nbsp;dopravcem záznam o&nbsp;poškození a&nbsp;pošlete nám fotografii – vyřešíme to.</p>
@endsection
