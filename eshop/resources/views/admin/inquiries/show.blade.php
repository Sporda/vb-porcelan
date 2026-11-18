@extends('layouts.admin')

@use('App\Http\Controllers\Admin\Support\Format')

@section('title', 'Poptávka '.$inquiry->number)

@php
    $type = $delivery['type'] ?? ($inquiry->pickup_point ? 'pickup_point' : ($inquiry->street ? 'address' : 'store'));
    $saving = $inquiry->rrp_total && $inquiry->rrp_total > $inquiry->items_total ? $inquiry->rrp_total - $inquiry->items_total : 0;
    $savingPct = Format::discountPercent($inquiry->items_total, $inquiry->rrp_total);
    $lineCount = $inquiry->items->count();
    $pieces = (int) $inquiry->items->sum('quantity');
    $statusError = $errors->status->first('status');
    $noteError = $errors->note->first('internal_note');
    $mailFailed = $inquiry->mail_status === 'failed';
    $flashAt = session('flash_at');
@endphp

@section('content')
    <div class="wrap">
        <div class="detail-top">
            <a class="back" href="{{ $backUrl }}">
                <svg class="ic" aria-hidden="true"><use href="#i-arrow-l"/></svg><span><span class="hide-xs">Všechny </span>poptávky</span>
            </a>
            <nav class="stepper" aria-label="Sousední poptávky">
                @if ($newer)
                    <a class="step-btn" href="{{ route('admin.inquiries.show', $newer) }}" rel="prev" aria-label="Novější poptávka {{ $newer->number }}">
                        <svg class="ic" aria-hidden="true"><use href="#i-chev-l"/></svg><span>Novější</span>
                    </a>
                @else
                    <span class="step-btn is-disabled" aria-hidden="true"><svg class="ic"><use href="#i-chev-l"/></svg><span>Novější</span></span>
                @endif
                @if ($older)
                    <a class="step-btn" href="{{ route('admin.inquiries.show', $older) }}" rel="next" aria-label="Starší poptávka {{ $older->number }}">
                        <span>Starší</span><svg class="ic" aria-hidden="true"><use href="#i-chev-r"/></svg>
                    </a>
                @else
                    <span class="step-btn is-disabled" aria-hidden="true"><span>Starší</span><svg class="ic"><use href="#i-chev-r"/></svg></span>
                @endif
            </nav>
        </div>

        <header class="detail-head">
            <div class="detail-id">
                <p class="eyebrow">Poptávka</p>
                <h1 class="h1 detail-number">{{ $inquiry->number }}</h1>
                <div class="detail-badges">
                    @include('admin.partials.status-badge', ['status' => $inquiry->status])
                    @include('admin.partials.mail-badge', ['mailStatus' => $inquiry->mail_status, 'long' => true])
                </div>
                <p class="detail-meta">
                    Přijato {{ Format::dateTime($inquiry->created_at) }}
                    <span class="sep" aria-hidden="true">·</span>
                    {{ Format::plural($pieces, 'kus', 'kusy', 'kusů') }} za {{ Format::money($inquiry->total) }}
                </p>
            </div>
            <div class="detail-actions">
                <a class="btn btn-primary" href="{{ Format::replyHref($inquiry->email, $inquiry->number) }}">
                    <svg class="ic" aria-hidden="true"><use href="#i-reply"/></svg>Odpovědět zákazníkovi
                </a>
            </div>
        </header>

        <div class="detail-grid">
            <div class="stack">
                <div class="pair">
                    <section class="panel" aria-labelledby="h-customer">
                        <h2 class="panel-title" id="h-customer">Zákazník</h2>
                        <p class="lead-name">{{ $inquiry->customer_name }}</p>
                        <ul class="contact">
                            <li>
                                <a href="mailto:{{ $inquiry->email }}"><svg class="ic" aria-hidden="true"><use href="#i-mail"/></svg><span>{{ $inquiry->email }}</span></a>
                            </li>
                            @if ($inquiry->phone)
                                <li>
                                    <a href="{{ Format::telHref($inquiry->phone) }}"><svg class="ic" aria-hidden="true"><use href="#i-phone"/></svg><span>{{ $inquiry->phone }}</span></a>
                                </li>
                            @endif
                        </ul>
                        @if ($inquiry->company || $inquiry->ic)
                            <dl class="facts">
                                @if ($inquiry->company)
                                    <div><dt>Firma</dt><dd>{{ $inquiry->company }}</dd></div>
                                @endif
                                @if ($inquiry->ic)
                                    <div><dt>IČO</dt><dd class="nums">{{ $inquiry->ic }}</dd></div>
                                @endif
                            </dl>
                        @endif
                    </section>

                    <section class="panel" aria-labelledby="h-delivery">
                        <h2 class="panel-title" id="h-delivery">Doručení</h2>
                        <p class="lead-name">{{ $inquiry->delivery_label }}</p>
                        <dl class="facts">
                            @if ($type === 'pickup_point' || $inquiry->pickup_point)
                                <div><dt>Výdejní místo</dt><dd>{{ $inquiry->pickup_point ?: 'neuvedeno' }}</dd></div>
                            @endif
                            @if ($type === 'address' || $inquiry->street || $inquiry->city)
                                <div>
                                    <dt>Adresa</dt>
                                    <dd>
                                        @if ($inquiry->street || $inquiry->city)
                                            {{ $inquiry->street }}<br>
                                            {{ trim($inquiry->zip.' '.$inquiry->city) }}<br>
                                            {{ Format::country($inquiry->country) }}
                                        @else
                                            neuvedena
                                        @endif
                                    </dd>
                                </div>
                            @endif
                            @if ($type === 'store')
                                <div><dt>Vyzvednutí</dt><dd>{{ config('shop.contact.address') }} · {{ config('shop.contact.hours') }}</dd></div>
                            @endif
                            <div><dt>Cena dopravy</dt><dd>{{ Format::shipping($inquiry->delivery_price) }}</dd></div>
                            @if (! empty($delivery['eta']))
                                <div><dt>Obvyklá doba</dt><dd>{{ $delivery['eta'] }}</dd></div>
                            @endif
                        </dl>
                    </section>
                </div>

                @if ($inquiry->note)
                    <section class="panel" aria-labelledby="h-note">
                        <h2 class="panel-title" id="h-note">Poznámka zákazníka</h2>
                        <blockquote class="quote"><p>{!! nl2br(e($inquiry->note)) !!}</p></blockquote>
                    </section>
                @endif

                <section class="panel panel-flush" aria-labelledby="h-items">
                    <div class="panel-head">
                        <h2 class="panel-title" id="h-items">Položky</h2>
                        <p class="panel-meta">{{ Format::plural($lineCount, 'položka', 'položky', 'položek') }} · {{ $pieces }} ks</p>
                    </div>

                    @if ($lineCount === 0)
                        <p class="panel-empty">Poptávka nemá žádné položky.</p>
                    @else
                        <ul class="items">
                            @foreach ($inquiry->items as $item)
                                @php
                                    $link = $productLinks[$item->id] ?? null;
                                    $img = Format::imageUrl($item->image_url);
                                    $itemPct = Format::discountPercent($item->unit_price, $item->rrp);
                                @endphp
                                <li class="item">
                                    <div class="item-media">
                                        @if ($img)
                                            <img src="{{ $img }}" width="64" height="80" loading="lazy" alt="">
                                        @else
                                            <svg class="ic" aria-hidden="true"><use href="#i-plate"/></svg>
                                        @endif
                                    </div>
                                    <div class="item-info">
                                        @if ($item->collection_name)
                                            <p class="item-coll">{{ $item->collection_name }}</p>
                                        @endif
                                        <p class="item-name">
                                            @if ($link)
                                                <a href="{{ $link }}" target="_blank" rel="noopener">{{ $item->product_name }}<span class="sr-only"> (stránka produktu, nová záložka)</span></a>
                                            @else
                                                {{ $item->product_name }}
                                            @endif
                                        </p>
                                        <p class="item-opt">
                                            @if ($item->option_label)
                                                <span>{{ $item->option_label }}</span>
                                            @endif
                                            @if ($itemPct)
                                                <span>Doporučená <s>{{ Format::money($item->rrp) }}</s> <span class="item-save">−{{ $itemPct }}&nbsp;%</span></span>
                                            @endif
                                        </p>
                                    </div>
                                    <p class="item-qty"><span class="sr-only">Množství a cena za kus: </span>{{ $item->quantity }}&nbsp;×&nbsp;{{ Format::money($item->unit_price) }}</p>
                                    <p class="item-total"><span class="sr-only">Celkem za položku: </span>{{ Format::money($item->line_total) }}</p>
                                </li>
                            @endforeach
                        </ul>
                    @endif

                    <dl class="totals">
                        <div><dt>Zboží</dt><dd>{{ Format::money($inquiry->items_total) }}</dd></div>
                        @if ($saving > 0)
                            <div class="totals-save">
                                <dt>Úspora proti doporučené ceně{{ $savingPct ? ' ('.$savingPct.' %)' : '' }}</dt>
                                <dd>−{{ Format::money($saving) }}</dd>
                            </div>
                        @endif
                        <div><dt>Doprava · {{ Format::deliveryShort($inquiry->delivery_label) }}</dt><dd>{{ Format::shipping($inquiry->delivery_price) }}</dd></div>
                        <div class="totals-grand"><dt>Celkem</dt><dd>{{ Format::money($inquiry->total) }}</dd></div>
                    </dl>
                </section>
            </div>

            <aside class="stack side" aria-label="Vyřízení poptávky">
                <section class="panel" aria-labelledby="h-status">
                    <h2 class="panel-title" id="h-status">Stav poptávky</h2>
                    <form class="form" method="POST" action="{{ route('admin.inquiries.status', $inquiry) }}">
                        @csrf
                        @method('PATCH')
                        <fieldset class="statuses" @if ($statusError) aria-describedby="status-error" @endif>
                            <legend class="sr-only">Nový stav poptávky</legend>
                            @foreach ($statuses as $key => $label)
                                <label class="status-opt status-opt-{{ $key }}">
                                    <input type="radio" name="status" value="{{ $key }}" @checked(old('status', $inquiry->status) === $key)>
                                    <span class="status-dot" aria-hidden="true"></span>
                                    <span class="status-label">{{ $label }}</span>
                                    @if ($inquiry->status === $key)
                                        <span class="status-now">teď</span>
                                    @endif
                                </label>
                            @endforeach
                        </fieldset>
                        @if ($statusError)
                            <p class="field-error" id="status-error"><svg class="ic" aria-hidden="true"><use href="#i-alert"/></svg>{{ $statusError }}</p>
                        @endif
                        <button class="btn btn-primary btn-block" type="submit">Uložit stav</button>
                    </form>
                </section>

                <section class="panel anchor" id="poznamka" aria-labelledby="h-internal">
                    <h2 class="panel-title" id="h-internal">Interní poznámka</h2>
                    @if ($flashAt === 'note')
                        @include('admin.partials.flash')
                    @endif
                    <form class="form" method="POST" action="{{ route('admin.inquiries.note', $inquiry) }}">
                        @csrf
                        @method('PATCH')
                        <div class="field">
                            <label class="field-label" for="internal_note">Poznámka pro obchod</label>
                            <textarea class="input textarea" id="internal_note" name="internal_note" rows="5" maxlength="{{ \App\Http\Requests\Admin\UpdateInquiryNoteRequest::MAX }}"
                                      aria-describedby="note-help{{ $noteError ? ' note-error' : '' }}" @if ($noteError) aria-invalid="true" @endif
                                      data-counter="note-count" placeholder="Např. volala 18. 11., chce doručit až po svátcích">{{ old('internal_note', $inquiry->internal_note) }}</textarea>
                            <p class="field-help" id="note-help">
                                <span>Zákazník ji neuvidí.</span>
                                <span class="counter" id="note-count" aria-live="polite">{{ mb_strlen((string) old('internal_note', $inquiry->internal_note)) }} / 5&nbsp;000</span>
                            </p>
                            @if ($noteError)
                                <p class="field-error" id="note-error"><svg class="ic" aria-hidden="true"><use href="#i-alert"/></svg>{{ $noteError }}</p>
                            @endif
                        </div>
                        <button class="btn btn-ghost btn-block" type="submit">Uložit poznámku</button>
                    </form>
                </section>

                <section class="panel anchor {{ $mailFailed ? 'panel-danger' : '' }}" id="email" aria-labelledby="h-email">
                    <h2 class="panel-title" id="h-email">E-mail s poptávkou</h2>
                    @if ($flashAt === 'email')
                        @include('admin.partials.flash')
                    @endif
                    <div class="mail-state">
                        @include('admin.partials.mail-badge', ['mailStatus' => $inquiry->mail_status, 'long' => true])
                    </div>
                    <dl class="facts">
                        <div><dt>Komu</dt><dd>{{ $inquiryTo }}</dd></div>
                        <div><dt>Naposledy odesláno</dt><dd>{{ $inquiry->mail_sent_at ? Format::dateTime($inquiry->mail_sent_at) : 'zatím ne' }}</dd></div>
                        @if ($inquiry->mail_info)
                            <div><dt>{{ $mailFailed ? 'Chyba' : 'Záznam' }}</dt><dd class="mono">{{ $inquiry->mail_info }}</dd></div>
                        @endif
                    </dl>
                    <form method="POST" action="{{ route('admin.inquiries.resend', $inquiry) }}">
                        @csrf
                        <button class="btn {{ $mailFailed ? 'btn-primary' : 'btn-ghost' }} btn-block" type="submit" data-busy="Odesílám…">
                            <svg class="ic" aria-hidden="true"><use href="#i-send"/></svg>Odeslat e-mail znovu
                        </button>
                    </form>
                    <p class="field-help">Pošle kopii poptávky znovu na {{ $inquiryTo }}. Zákazníkovi nic nepřijde.</p>
                </section>
            </aside>
        </div>
    </div>
@endsection
