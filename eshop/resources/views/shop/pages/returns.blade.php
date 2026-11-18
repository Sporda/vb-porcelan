@extends('shop.pages.layout')

@section('title', 'Reklamace a vrácení')
@section('description', 'Jak vrátit zboží do 14 dní bez udání důvodu a jak reklamovat porcelán z e-shopu Bílé zlato.')
@section('page_label', 'Po nákupu')
@section('page_h1')Reklamace <em>a&nbsp;vrácení</em>@endsection
@section('draft', 'Vzor – provozovatel doplní adresu pro vrácení zboží a před spuštěním obchodu text zkontroluje.')

@section('page_body')
  <h2>Vrácení do 14 dní</h2>
  <p>Zboží můžete do 14&nbsp;dní od převzetí vrátit bez udání důvodu.</p>
  <ol>
    <li>Napište nám e-mail s&nbsp;číslem poptávky a&nbsp;tím, co vracíte.</li>
    <li>Porcelán pečlivě zabalte, nejlépe do původní krabice, a&nbsp;pošlete na adresu, kterou vám obratem sdělíme.</li>
    <li>Peníze vrátíme do 14&nbsp;dní od odstoupení na váš účet, nejdříve ale po doručení vráceného zboží. Náklady na zpětnou dopravu hradí kupující.</li>
  </ol>

  <h2>Poškozená zásilka</h2>
  <p>Když krabice dorazí viditelně poškozená, sepište s&nbsp;dopravcem záznam o&nbsp;poškození. Rozbitý kus vyfoťte i&nbsp;s&nbsp;obalem a&nbsp;pošlete nám fotky e-mailem – pošleme nový kus, nebo vrátíme peníze.</p>

  <h2>Reklamace vady</h2>
  <p>Na porcelán se vztahuje zákonná lhůta 24&nbsp;měsíců od převzetí. Vadu nám popište e-mailem a&nbsp;přiložte fotografii. Reklamaci vyřídíme bez zbytečného odkladu, nejpozději do 30&nbsp;dní.</p>
  <p>Za vadu se nepovažuje běžné opotřebení, poškození nevhodným zacházením (například zlatý dekor v&nbsp;mikrovlnné troubě) ani drobné rozdíly v&nbsp;odstínu glazury mezi výrobními sériemi.</p>

  <p class="prose-cta"><a class="btn btn-primary" href="mailto:{{ config('shop.contact.email') }}?subject={{ rawurlencode('Vrácení / reklamace') }}"><svg class="ic" aria-hidden="true"><use href="#i-mail"/></svg> Napsat e-mail</a></p>
@endsection
