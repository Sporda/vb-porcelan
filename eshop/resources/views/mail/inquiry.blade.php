{{-- Dočasná šablona (základ). Finální podobu dělá obchod (storefront). --}}
<h1>Nová poptávka {{ $inquiry->number }}</h1>
<p>{{ $inquiry->customer_name }} · {{ $inquiry->email }} · {{ $inquiry->phone }}</p>
<ul>
@foreach ($inquiry->items as $item)
    <li>{{ $item->quantity }}× {{ $item->product_name }} – {{ $item->line_total }} Kč</li>
@endforeach
</ul>
<p>Celkem {{ $inquiry->total }} Kč</p>
