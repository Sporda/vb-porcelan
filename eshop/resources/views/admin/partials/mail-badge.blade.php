{{-- Stav e-mailu s poptávkou: $mailStatus (sent | failed | pending); $long = celá věta (detail) --}}
@php
    $long = $long ?? false;
    [$icon, $short, $sentence] = match ($mailStatus) {
        'sent' => ['i-check', 'Odesláno', 'E-mail odeslán'],
        'failed' => ['i-alert', 'Neodesláno', 'E-mail se nepodařilo odeslat'],
        default => ['i-clock', 'Čeká', 'E-mail čeká na odeslání'],
    };
@endphp
@if ($long)
    <span class="mail mail-{{ $mailStatus }}"><svg class="ic" aria-hidden="true"><use href="#{{ $icon }}"/></svg>{{ $sentence }}</span>
@else
    <span class="mail mail-{{ $mailStatus }}" title="{{ $sentence }}"><svg class="ic" aria-hidden="true"><use href="#{{ $icon }}"/></svg><span class="sr-only">E-mail: </span>{{ $short }}</span>
@endif
