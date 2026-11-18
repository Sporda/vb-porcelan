@php
    // Textová verze e-mailu – čistý text, proto výpisy bez HTML escapování ({!! !!}).
    use App\View\Fmt;
    $i = $inquiry;
    $saving = $i->rrp_total ? max(0, $i->rrp_total - $i->items_total) : 0;
    $adminUrl = \Illuminate\Support\Facades\Route::has('admin.inquiries.show') ? route('admin.inquiries.show', $i) : null;
    $address = $i->pickup_point ?: ($i->street ? trim($i->street.', '.$i->zip.' '.$i->city, ', ') : null);
@endphp
NOVÁ POPTÁVKA {!! $i->number !!}
{!! $i->created_at?->timezone('Europe/Prague')->format('j. n. Y, H:i') !!}

Odpovězte na tento e-mail – odpověď půjde zákazníkovi.

ZÁKAZNÍK
{!! $i->customer_name !!}
{!! $i->email !!}
@if ($i->phone)
{!! $i->phone !!}
@endif
@if ($i->company)
{!! $i->company !!}{!! $i->ic ? ', IČO '.$i->ic : '' !!}
@elseif ($i->ic)
IČO {!! $i->ic !!}
@endif

DOPRAVA
{!! $i->delivery_label !!} – {!! $i->delivery_price ? Fmt::price($i->delivery_price) : 'zdarma' !!}
@if ($address)
{!! $i->pickup_point ? 'Výdejní místo: ' : '' !!}{!! $address !!}
@endif
@if ($i->note)

POZNÁMKA
{!! $i->note !!}
@endif

POLOŽKY
@foreach ($i->items as $item)
- {!! $item->quantity !!}× {!! $item->collection_name ? $item->collection_name.' – ' : '' !!}{!! $item->product_name !!}{!! $item->option_label ? ' ('.$item->option_label.')' : '' !!}: {!! Fmt::price($item->unit_price) !!} / ks = {!! Fmt::price($item->line_total) !!}
@endforeach

Zboží: {!! Fmt::price($i->items_total) !!}
@if ($saving > 0)
Úspora proti doporučené ceně: {!! Fmt::price($saving) !!}
@endif
Doprava: {!! $i->delivery_price ? Fmt::price($i->delivery_price) : 'zdarma' !!}
CELKEM: {!! Fmt::price($i->total) !!}
@if ($adminUrl)

Administrace: {!! $adminUrl !!}
@endif
