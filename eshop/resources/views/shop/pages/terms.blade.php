@extends('shop.pages.layout')

@section('title', 'Obchodní podmínky')
@section('description', 'Obchodní podmínky e-shopu Bílé zlato (vzor pro ukázkovou verzi).')
@section('page_label', 'Právní informace')
@section('page_h1')Obchodní <em>podmínky</em>@endsection
@section('draft', 'Vzor – provozovatel doplní obchodní firmu, sídlo, IČO a kontaktní údaje a text před spuštěním obchodu nechá zkontrolovat. Ukázková verze nepřijímá závazné objednávky.')

@section('page_body')
  <h2>1. Úvodní ustanovení</h2>
  <p>Tyto podmínky upravují nákup v&nbsp;e-shopu Bílé zlato. Prodávajícím je provozovatel e-shopu (identifikační údaje doplní provozovatel), kupujícím je spotřebitel nebo podnikatel, který odešle poptávku. Vztahy neupravené těmito podmínkami se řídí občanským zákoníkem (zákon č.&nbsp;89/2012&nbsp;Sb.) a&nbsp;zákonem o&nbsp;ochraně spotřebitele (zákon č.&nbsp;634/1992&nbsp;Sb.).</p>

  <h2>2. Poptávka a&nbsp;uzavření smlouvy</h2>
  <p>Odesláním košíku vzniká nezávazná poptávka. Prodávající ověří dostupnost a&nbsp;e-mailem pošle potvrzení s&nbsp;konečnou cenou. Kupní smlouva vzniká až tímto potvrzením. Do té doby může kupující poptávku zrušit a&nbsp;prodávající ji může odmítnout, například když zboží není dostupné.</p>

  <h2>3. Cena a&nbsp;platba</h2>
  <p>Ceny jsou uvedeny v&nbsp;korunách včetně DPH. Cena dopravy se řídí stránkou <a class="text-link" href="{{ route('pages.delivery') }}">Doprava a&nbsp;platba</a>. Kupující platí po potvrzení objednávky bankovním převodem nebo na dobírku.</p>

  <h2>4. Dodání</h2>
  <p>Skladové zboží prodávající odešle do 24&nbsp;hodin od přijetí platby (u&nbsp;dobírky od potvrzení). U&nbsp;zboží na objednávku uvede termín v&nbsp;potvrzení. Kupující zkontroluje zásilku při převzetí; poškozenou zásilku sepíše s&nbsp;dopravcem.</p>

  <h2>5. Odstoupení od smlouvy</h2>
  <p>Spotřebitel může od smlouvy odstoupit bez udání důvodu do 14&nbsp;dní od převzetí zboží. Postup popisuje stránka <a class="text-link" href="{{ route('pages.returns') }}">Reklamace a&nbsp;vrácení</a>. Prodávající vrátí přijaté peníze do 14&nbsp;dní od odstoupení, nejdříve však po vrácení zboží.</p>

  <h2>6. Práva z&nbsp;vadného plnění</h2>
  <p>Spotřebitel může vady uplatnit do 24&nbsp;měsíců od převzetí. Reklamaci prodávající vyřídí bez zbytečného odkladu, nejpozději do 30&nbsp;dní.</p>

  <h2>7. Osobní údaje</h2>
  <p>Zpracování osobních údajů popisuje stránka <a class="text-link" href="{{ route('pages.privacy') }}">Ochrana osobních údajů</a>.</p>

  <h2>8. Mimosoudní řešení sporů</h2>
  <p>Ke mimosoudnímu řešení spotřebitelských sporů je příslušná Česká obchodní inspekce (www.coi.cz).</p>

  <h2>9. Závěrečná ustanovení</h2>
  <p>Podmínky platí ve znění zveřejněném v&nbsp;den odeslání poptávky. Datum účinnosti doplní provozovatel.</p>
@endsection
