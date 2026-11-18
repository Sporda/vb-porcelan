Nová poptávka {{ $inquiry->number }}

{{ $inquiry->customer_name }} · {{ $inquiry->email }} · {{ $inquiry->phone }}

@foreach ($inquiry->items as $item)
- {{ $item->quantity }}× {{ $item->product_name }} – {{ $item->line_total }} Kč
@endforeach

Celkem {{ $inquiry->total }} Kč
