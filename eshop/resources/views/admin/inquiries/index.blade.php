@extends('layouts.admin')

@use('App\Http\Controllers\Admin\Support\Format')

@section('title', 'Poptávky')

@php
    $filterQuery = array_filter(['q' => $term, 'email' => $onlyFailed ? 'failed' : null]);
    $isFiltered = $term !== '' || $onlyFailed;
@endphp

@section('content')
    <div class="wrap">
        <header class="page-head">
            <div class="page-title">
                <h1 class="h1">Poptávky</h1>
                <p class="page-sub">
                    @if ($grandTotal === 0)
                        Zatím žádná poptávka
                    @else
                        {{ Format::plural($grandTotal, 'poptávka', 'poptávky', 'poptávek') }} celkem
                        @if ($newCount > 0)
                            <span class="sep" aria-hidden="true">·</span>
                            <a class="sub-link" href="{{ route('admin.inquiries.index', ['stav' => 'new']) }}">{{ Format::plural($newCount, 'nová', 'nové', 'nových') }} k vyřízení</a>
                        @endif
                    @endif
                </p>
            </div>

            <form class="search" method="GET" action="{{ route('admin.inquiries.index') }}" role="search">
                @if ($status !== '')
                    <input type="hidden" name="stav" value="{{ $status }}">
                @endif
                @if ($onlyFailed)
                    <input type="hidden" name="email" value="failed">
                @endif
                <label class="field-label" for="q">Hledat číslo, jméno, e-mail nebo telefon</label>
                <div class="search-row">
                    <div class="search-input">
                        <svg class="ic" aria-hidden="true"><use href="#i-search"/></svg>
                        <input class="input" id="q" name="q" type="search" value="{{ $term }}" maxlength="100" autocomplete="off" spellcheck="false" placeholder="např. Nováková">
                    </div>
                    <button class="btn btn-ghost" type="submit">Hledat</button>
                </div>
            </form>
        </header>

        @if ($failedCount > 0 && ! $onlyFailed)
            <div class="notice notice-danger" role="status">
                <svg class="ic" aria-hidden="true"><use href="#i-alert"/></svg>
                <p>
                    U {{ Format::plural($failedCount, 'poptávky', 'poptávek', 'poptávek') }} se nepodařilo odeslat e-mail na {{ config('shop.inquiry_to') }}.
                    <a href="{{ route('admin.inquiries.index', ['email' => 'failed']) }}">Zobrazit {{ $failedCount === 1 ? 'ji' : 'je' }}</a>
                </p>
            </div>
        @endif

        <nav class="tabs" aria-label="Filtr podle stavu">
            @foreach ($tabs as $key => $label)
                @php($tabUrl = route('admin.inquiries.index', $key === '' ? $filterQuery : ['stav' => $key] + $filterQuery))
                <a class="tab {{ $key === 'new' && $counts[$key] > 0 ? 'tab-attn' : '' }}" href="{{ $tabUrl }}" @if ($status === $key) aria-current="page" @endif>
                    {{ $label }} <span class="tab-count">{{ $counts[$key] }}</span>
                </a>
            @endforeach
        </nav>

        @if ($isFiltered)
            <div class="filters-on">
                @if ($term !== '')
                    <span class="filters-text">Výsledky pro „{{ $term }}“</span>
                    <a class="chip" href="{{ route('admin.inquiries.index', array_filter(['stav' => $status ?: null, 'email' => $onlyFailed ? 'failed' : null])) }}" aria-label="Zrušit hledání „{{ $term }}“">
                        Zrušit hledání <svg class="ic" aria-hidden="true"><use href="#i-close"/></svg>
                    </a>
                @endif
                @if ($onlyFailed)
                    <a class="chip chip-danger" href="{{ route('admin.inquiries.index', array_filter(['stav' => $status ?: null, 'q' => $term ?: null])) }}" aria-label="Zrušit filtr: jen neodeslané e-maily">
                        Jen neodeslané e-maily <svg class="ic" aria-hidden="true"><use href="#i-close"/></svg>
                    </a>
                @endif
            </div>
        @endif

        @if ($inquiries->isEmpty())
            <section class="panel empty" aria-labelledby="empty-title">
                <svg class="ic empty-ic" aria-hidden="true"><use href="#i-inbox"/></svg>
                @if ($grandTotal === 0)
                    <h2 class="h2" id="empty-title">Zatím tu žádné poptávky nejsou</h2>
                    <p>Jakmile zákazník odešle košík jako poptávku, objeví se tady a zároveň přijde e-mailem na {{ config('shop.inquiry_to') }}.</p>
                    <a class="btn btn-ghost" href="{{ url('/') }}" target="_blank" rel="noopener">Otevřít e-shop</a>
                @else
                    <h2 class="h2" id="empty-title">Nic jsme nenašli</h2>
                    <p>
                        @if ($term !== '')
                            Žádná poptávka{{ $status !== '' ? ' ve stavu „'.\App\Models\Inquiry::STATUSES[$status].'“' : '' }} neodpovídá hledání „{{ $term }}“. Zkuste jen část jména, e-mailu nebo čísla.
                        @else
                            V tomto výběru teď žádná poptávka není.
                        @endif
                    </p>
                    <a class="btn btn-ghost" href="{{ route('admin.inquiries.index') }}">Zobrazit všechny poptávky</a>
                @endif
            </section>
        @else
            <div class="panel table-panel">
                <table class="itable">
                    <caption class="sr-only">Poptávky, nejnovější nahoře</caption>
                    <thead>
                        <tr>
                            <th scope="col">Číslo</th>
                            <th scope="col">Přijato</th>
                            <th scope="col">Zákazník</th>
                            <th scope="col" class="t-items">Položky</th>
                            <th scope="col" class="num">Celkem</th>
                            <th scope="col" class="t-delivery">Doprava</th>
                            <th scope="col">Stav</th>
                            <th scope="col">E-mail</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($inquiries as $inquiry)
                            @php($url = route('admin.inquiries.show', $inquiry))
                            <tr class="row {{ $inquiry->status === 'new' ? 'is-new' : '' }} {{ $inquiry->mail_status === 'failed' ? 'is-failed' : '' }}" data-href="{{ $url }}">
                                <td class="t-num">
                                    <a class="row-link" href="{{ $url }}">{{ $inquiry->number }}</a>
                                </td>
                                <td class="t-date">
                                    <span class="t-day">{{ Format::day($inquiry->created_at) }}</span>
                                    <span class="sub">{{ Format::time($inquiry->created_at) }}</span>
                                </td>
                                <td class="t-cust">
                                    <strong>{{ $inquiry->customer_name }}</strong>
                                    <span class="sub t-email" title="{{ $inquiry->email }}">{{ $inquiry->email }}</span>
                                    @if ($inquiry->company)
                                        <span class="sub t-company" title="{{ $inquiry->company }}">{{ $inquiry->company }}</span>
                                    @endif
                                </td>
                                <td class="t-items">
                                    {{ Format::plural((int) $inquiry->items_count, 'položka', 'položky', 'položek') }}
                                    <span class="sub hide-cards">{{ (int) $inquiry->items_quantity }} ks</span>
                                </td>
                                <td class="t-total num">{{ Format::money($inquiry->total) }}</td>
                                <td class="t-delivery">
                                    {{ Format::deliveryShort($inquiry->delivery_label) }}
                                    <span class="sub hide-cards">{{ Format::shipping($inquiry->delivery_price) }}</span>
                                </td>
                                <td class="t-status">@include('admin.partials.status-badge', ['status' => $inquiry->status])</td>
                                <td class="t-mail">@include('admin.partials.mail-badge', ['mailStatus' => $inquiry->mail_status])</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{ $inquiries->onEachSide(1)->links('admin.partials.pagination') }}
        @endif
    </div>
@endsection
