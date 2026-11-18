{{-- Stav poptávky: $status (klíč z Inquiry::STATUSES) --}}
@php($label = \App\Models\Inquiry::STATUSES[$status] ?? $status)
<span class="badge badge-{{ $status }}"><span class="badge-dot" aria-hidden="true"></span>{{ $label }}</span>
