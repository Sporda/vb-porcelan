@extends('layouts.shop')
@php
    use App\View\Fmt;
    $i = $inquiry;
    $address = match (true) {
        (bool) $i->pickup_point => $i->pickup_point,
        (bool) $i->street => trim($i->street.', '.$i->zip.' '.$i->city, ', '),
        default => null,
    };
    $saving = $i->rrp_total ? max(0, $i->rrp_total - $i->items_total) : 0;
@endphp

@section('title', 'Poptávka '.$i->number.' odeslána')
@section('description', 'Děkujeme za poptávku. Ověříme dostupnost a pošleme potvrzení s platebními údaji.')

@section('content')
<div class="wrap">
  <header class="page-head thanks-head">
    <p class="label">Poptávka {{ $i->number }}</p>
    <h1>Děkujeme, poptávka <em>je odeslaná</em></h1>
    <p class="lead">Nic jste zatím neplatili. Ověříme dostupnost všech kusů a&nbsp;na <b>{{ $i->email }}</b> pošleme potvrzení s&nbsp;platebními údaji.</p>
  </header>

  <div class="thanks-grid page-body">
    <div>
      <ol class="next-steps">
        <li><span class="n">01</span><div><p class="step-t">Ověříme dostupnost</p><p>Projdeme sklad a&nbsp;u&nbsp;kusů na objednávku zjistíme termín dodání.</p></div></li>
        <li><span class="n">02</span><div><p class="step-t">Pošleme potvrzení</p><p>E-mailem s&nbsp;konečnou cenou a&nbsp;platebními údaji – zaplatíte převodem nebo na dobírku.</p></div></li>
        <li><span class="n">03</span><div><p class="step-t">Zabalíme a&nbsp;odešleme</p><p>Každý kus balíme ručně a&nbsp;posíláme pojištěně. Doprava: {{ $i->delivery_label }}.</p></div></li>
      </ol>
      <p class="thanks-note">Chcete něco změnit? <a class="text-link" href="mailto:{{ config('shop.contact.email') }}?subject={{ rawurlencode('Poptávka '.$i->number) }}">Napište nám</a> a&nbsp;uveďte číslo <span class="nowrap">{{ $i->number }}</span>.</p>
      <p class="empty-links"><a class="btn btn-ghost" href="{{ route('home') }}">Zpět na úvod</a></p>
    </div>

    <aside class="summary" aria-labelledby="h-thx-sum">
      <h2 id="h-thx-sum" class="summary-h">Souhrn poptávky</h2>
      <ul class="mini-lines">
        @foreach ($i->items as $item)
          <li>
            @if ($item->image_url)<img src="{{ Fmt::img($item->image_url, 160) }}" width="56" height="70" loading="lazy" alt="">@endif
            <div>
              <p class="ml-name">{{ $item->product_name }}</p>
              <p class="ml-meta">{{ $item->collection_name }}{{ $item->option_label ? ' · '.$item->option_label : '' }} · {{ $item->quantity }}&nbsp;×&nbsp;{{ Fmt::price($item->unit_price) }}</p>
            </div>
            <b>{{ Fmt::price($item->line_total) }}</b>
          </li>
        @endforeach
      </ul>
      <dl class="sum-rows">
        <div><dt>Zboží</dt><dd>{{ Fmt::price($i->items_total) }}</dd></div>
        @if ($saving > 0)<div class="save"><dt>Ušetříte</dt><dd>{{ Fmt::price($saving) }}</dd></div>@endif
        <div><dt>{{ $i->delivery_label }}</dt><dd>{{ $i->delivery_price ? Fmt::price($i->delivery_price) : 'zdarma' }}</dd></div>
        <div class="total"><dt>Celkem</dt><dd>{{ Fmt::price($i->total) }}</dd></div>
      </dl>
      <dl class="contact-rows">
        <div><dt>Kontakt</dt><dd>{{ $i->customer_name }}<br>{{ $i->email }} · {{ $i->phone }}</dd></div>
        @if ($i->company)<div><dt>Firma</dt><dd>{{ $i->company }}{{ $i->ic ? ', IČO '.$i->ic : '' }}</dd></div>@endif
        @if ($address)<div><dt>{{ $i->pickup_point ? 'Výdejní místo' : 'Adresa' }}</dt><dd>{{ $address }}</dd></div>@endif
        @if ($i->note)<div><dt>Poznámka</dt><dd>{{ $i->note }}</dd></div>@endif
      </dl>
    </aside>
  </div>
</div>
@endsection
