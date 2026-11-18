@extends('shop.pages.layout')
@php
    use App\View\Fmt;
    $px = fn (int $id, int $w) => "https://images.pexels.com/photos/{$id}/pexels-photo-{$id}.jpeg?auto=compress&cs=tinysrgb&w={$w}";
@endphp

@section('title', 'O obchodě')
@section('description', 'Bílé zlato prodává nový, originální porcelán Villeroy & Boch nakoupený přímo u výrobce. Kdo jsme, co prodáváme a jak objednávky vyřizujeme.')
@section('page_label', 'O nás')
@section('page_h1')Porcelán Villeroy &amp; Boch <em>z&nbsp;první ruky</em>@endsection
@section('page_lead', 'Bílé zlato je český e-shop s novým, originálním porcelánem Villeroy & Boch. Nakupujeme přímo u výrobce a prodáváme jen online.')

@section('page_body')
  <h2>Co prodáváme</h2>
  <p>Talíře, šálky, misky, servírovací kusy a&nbsp;celé jídelní sady z&nbsp;{{ Fmt::count($stats['collections'], 'kolekce', 'kolekcí', 'kolekcí') }} Villeroy &amp; Boch – od hladkého bílého porcelánu Royal po kostní porcelán Anmut Gold se zlatou linkou. Právě teď nabízíme {{ Fmt::count($stats['products'], 'produkt', 'produkty', 'produktů') }}, všechny nové a&nbsp;nepoužité.</p>

  <h2>Jak objednávka probíhá</h2>
  <p>Košík odešlete jako nezávaznou poptávku. Ověříme dostupnost, pošleme potvrzení s&nbsp;platebními údaji a&nbsp;po zaplacení porcelán zabalíme a&nbsp;odešleme. Skladové kusy obvykle do 24&nbsp;hodin od potvrzení.</p>

  <h2>Jak balíme</h2>
  <p>Každý kus balíme ručně do pevné krabice s&nbsp;recyklovatelnou výplní. Zásilky posíláme pojištěné, takže kdyby se cestou něco rozbilo, pošleme nový kus nebo vrátíme peníze.</p>

  <h2>Kde nás najdete</h2>
  <p>Sídlíme v&nbsp;Praze, kde je možný i&nbsp;osobní odběr po předchozí domluvě. Napište nám přes <a class="text-link" href="{{ route('pages.contact') }}">kontaktní stránku</a>.</p>
@endsection

@section('page_aside')
  <div class="info-photo"><img src="{{ $px(18977895, 700) }}" srcset="{{ $px(18977895, 500) }} 500w, {{ $px(18977895, 900) }} 900w" sizes="(max-width: 1024px) 92vw, 30vw" width="700" height="933" loading="lazy" alt="Stoh bílých reliéfních talířů a šálek na lněném ubrusu"></div>
@endsection
