@extends('shop.pages.layout')

@section('title', 'Ochrana osobních údajů')
@section('description', 'Jaké osobní údaje e-shop Bílé zlato zpracovává, proč a jak dlouho (vzor pro ukázkovou verzi).')
@section('page_label', 'Právní informace')
@section('page_h1')Ochrana <em>osobních údajů</em>@endsection
@section('draft', 'Vzor – provozovatel doplní totožnost a kontakt správce údajů a text před spuštěním obchodu nechá zkontrolovat.')

@section('page_body')
  <h2>Správce údajů</h2>
  <p>Správcem je provozovatel e-shopu Bílé zlato (identifikační údaje doplní provozovatel). Dotazy k&nbsp;osobním údajům posílejte e-mailem přes stránku <a class="text-link" href="{{ route('pages.contact') }}">Kontakt</a>.</p>

  <h2>Jaké údaje zpracováváme</h2>
  <ul>
    <li><b>Z&nbsp;poptávky:</b> jméno a&nbsp;příjmení, e-mail, telefon, doručovací adresu nebo výdejní místo, případně firmu a&nbsp;IČO a&nbsp;poznámku.</li>
    <li><b>Technické údaje:</b> otisk IP adresy (jednosměrný hash, ne samotnou adresu) a&nbsp;typ prohlížeče u&nbsp;odeslané poptávky – kvůli ochraně před zneužitím formuláře.</li>
    <li><b>Košík a&nbsp;oblíbené:</b> obsah se ukládá jen do relace prohlížeče a&nbsp;není spojen s&nbsp;vaším jménem.</li>
  </ul>

  <h2>Proč a&nbsp;na jakém základě</h2>
  <p>Údaje z&nbsp;poptávky potřebujeme k&nbsp;jejímu vyřízení a&nbsp;k&nbsp;uzavření a&nbsp;plnění kupní smlouvy (čl.&nbsp;6 odst.&nbsp;1 písm.&nbsp;b GDPR). Údaje z&nbsp;daňových dokladů uchováváme kvůli zákonné povinnosti (písm.&nbsp;c). Technické údaje zpracováváme z&nbsp;oprávněného zájmu na bezpečnosti webu (písm.&nbsp;f).</p>

  <h2>Jak dlouho</h2>
  <p>Poptávku, ze které nevznikne objednávka, smažeme nejpozději do 12&nbsp;měsíců. Údaje k&nbsp;uzavřeným objednávkám uchováváme po dobu stanovenou účetními a&nbsp;daňovými předpisy.</p>

  <h2>Komu údaje předáváme</h2>
  <p>Dopravci, kterého si vyberete (jméno, telefon, adresa nebo výdejní místo), poskytovateli hostingu a&nbsp;e-mailových služeb. Údaje neprodáváme a&nbsp;nepoužíváme k&nbsp;reklamě.</p>

  <h2>Cookies</h2>
  <p>Web používá jen nezbytné cookies: relaci (aby fungoval košík a&nbsp;oblíbené) a&nbsp;ochranný token formulářů. Analytické ani reklamní cookies nepoužíváme.</p>

  <h2>Vaše práva</h2>
  <p>Máte právo na přístup ke svým údajům, jejich opravu, výmaz, omezení zpracování, přenositelnost a&nbsp;právo vznést námitku. Stížnost můžete podat u&nbsp;Úřadu pro ochranu osobních údajů (www.uoou.cz).</p>
@endsection
