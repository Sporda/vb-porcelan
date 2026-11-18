@extends('shop.pages.layout')

@section('title', 'Kontakt')
@section('description', 'Kontakt na e-shop Bílé zlato – dotazy k porcelánu Villeroy & Boch, objednávkám a dopravě.')
@section('page_label', 'Jsme tu pro vás')
@section('page_h1')Kontakt <em>a&nbsp;dotazy</em>@endsection
@section('page_lead', 'Ptejte se na dekory, rozměry, dostupnost i větší odběry pro restaurace. Odpovídáme e-mailem.')

@section('page_body')
  <div class="contact-card">
    <p class="label">E-mail</p>
    <p class="contact-big">Napište nám</p>
    <p>Odpovídáme v&nbsp;pracovní dny {{ str_replace('Po–Pá ', '', $contact['hours'] ?? '') }}&nbsp;h. K&nbsp;dotazu na objednávku připište číslo poptávky.</p>
    <p><a class="btn btn-primary" href="mailto:{{ $contact['email'] }}"><svg class="ic" aria-hidden="true"><use href="#i-mail"/></svg> Napsat e-mail</a></p>
  </div>

  <h2>Provozní doba</h2>
  <p>{{ $contact['hours'] ?? '' }}&nbsp;h. Poptávky z&nbsp;víkendu vyřizujeme v&nbsp;pondělí.</p>

  @if (! empty($contact['phone']))
    <h2>Telefon</h2>
    <p><a class="text-link" href="tel:{{ preg_replace('/\s+/', '', $contact['phone']) }}">{{ $contact['phone'] }}</a></p>
  @endif

  <h2>Osobní odběr</h2>
  <p>{{ $contact['address'] ?? 'Praha' }} – po předchozí domluvě. Při objednávce zvolte „Osobní odběr“ a&nbsp;místo i&nbsp;čas upřesníme v&nbsp;potvrzovacím e-mailu.</p>

  <h2>Údaje provozovatele</h2>
  <p>Ukázková verze e-shopu. Obchodní firmu, sídlo a&nbsp;IČO doplní provozovatel před spuštěním obchodu.</p>
@endsection
