@php
    use App\View\Fmt;
    $i = $inquiry;
    $saving = $i->rrp_total ? max(0, $i->rrp_total - $i->items_total) : 0;
    $adminUrl = \Illuminate\Support\Facades\Route::has('admin.inquiries.show') ? route('admin.inquiries.show', $i) : null;
    $tel = $i->phone ? preg_replace('/[^\d+]/', '', $i->phone) : null;
    $address = $i->pickup_point ?: ($i->street ? trim($i->street.', '.$i->zip.' '.$i->city, ', ') : null);
    $ink = '#24282b'; $muted = '#4b524c'; $line = '#d9e0d1'; $brass = '#7a5a26'; $olive = '#4f5d3a';
    $font = "font-family:'Helvetica Neue',Helvetica,Arial,sans-serif;";
    $serif = "font-family:Georgia,'Times New Roman',serif;";
@endphp
<!doctype html>
<html lang="cs">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="color-scheme" content="light">
<title>Nová poptávka {{ $i->number }}</title>
</head>
<body style="margin:0;padding:0;background:#dde4d6;">
<div style="display:none;max-height:0;overflow:hidden;">{{ $i->customer_name }} · {{ $i->items->sum('quantity') }} ks · {{ Fmt::price($i->total) }} · {{ $i->delivery_label }}</div>
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#dde4d6;">
  <tr>
    <td align="center" style="padding:32px 12px;">
      <table role="presentation" width="640" cellpadding="0" cellspacing="0" border="0" style="width:100%;max-width:640px;background:#f6f8f2;border:1px solid #cfd8c5;">
        {{-- Hlavička --}}
        <tr>
          <td style="padding:28px 32px 20px;border-bottom:1px solid {{ $line }};">
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
              <tr>
                <td style="{{ $serif }}font-size:26px;line-height:1;color:{{ $ink }};">Bílé <i style="color:{{ $brass }};">zlato</i></td>
                <td align="right" style="{{ $font }}font-size:11px;letter-spacing:2px;text-transform:uppercase;color:{{ $muted }};">Nová poptávka</td>
              </tr>
            </table>
          </td>
        </tr>
        <tr>
          <td style="padding:28px 32px 8px;">
            <p style="margin:0;{{ $font }}font-size:11px;letter-spacing:2px;text-transform:uppercase;color:{{ $brass }};">{{ $i->created_at?->timezone('Europe/Prague')->format('j. n. Y, H:i') }}</p>
            <h1 style="margin:8px 0 0;{{ $serif }}font-weight:normal;font-size:30px;line-height:1.15;color:{{ $ink }};">Poptávka {{ $i->number }}</h1>
            <p style="margin:10px 0 0;{{ $font }}font-size:15px;line-height:1.55;color:{{ $muted }};">Zákazník čeká na potvrzení dostupnosti a&nbsp;platební údaje. <b style="color:{{ $ink }};">Odpovězte na tento e-mail – odpověď půjde zákazníkovi.</b></p>
          </td>
        </tr>

        {{-- Zákazník a doprava --}}
        <tr>
          <td style="padding:20px 32px 4px;">
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="{{ $font }}font-size:14px;line-height:1.55;color:{{ $ink }};">
              <tr>
                <td valign="top" width="50%" style="padding:0 16px 16px 0;">
                  <p style="margin:0 0 6px;font-size:11px;letter-spacing:2px;text-transform:uppercase;color:{{ $muted }};">Zákazník</p>
                  <b>{{ $i->customer_name }}</b><br>
                  <a href="mailto:{{ $i->email }}" style="color:{{ $brass }};">{{ $i->email }}</a><br>
                  @if ($tel)<a href="tel:{{ $tel }}" style="color:{{ $brass }};">{{ $i->phone }}</a><br>@endif
                  @if ($i->company){{ $i->company }}@if ($i->ic), IČO {{ $i->ic }}@endif<br>@elseif ($i->ic)IČO {{ $i->ic }}<br>@endif
                </td>
                <td valign="top" width="50%" style="padding:0 0 16px 16px;">
                  <p style="margin:0 0 6px;font-size:11px;letter-spacing:2px;text-transform:uppercase;color:{{ $muted }};">Doprava</p>
                  <b>{{ $i->delivery_label }}</b><br>
                  @if ($address){{ $i->pickup_point ? 'Výdejní místo: ' : '' }}{{ $address }}<br>@endif
                  {{ $i->delivery_price ? Fmt::price($i->delivery_price) : 'zdarma' }}
                </td>
              </tr>
            </table>
            @if ($i->note)
              <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin:0 0 16px;">
                <tr><td style="padding:14px 16px;background:#e9eee3;border-left:3px solid {{ $brass }};{{ $font }}font-size:14px;line-height:1.55;color:{{ $ink }};">
                  <span style="font-size:11px;letter-spacing:2px;text-transform:uppercase;color:{{ $muted }};">Poznámka zákazníka</span><br>{!! nl2br(e($i->note)) !!}
                </td></tr>
              </table>
            @endif
          </td>
        </tr>

        {{-- Položky --}}
        <tr>
          <td style="padding:0 32px;">
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="{{ $font }}font-size:14px;line-height:1.45;color:{{ $ink }};border-top:1px solid {{ $ink }};">
              <tr>
                <th align="left" style="padding:10px 0;font-size:11px;font-weight:normal;letter-spacing:1.5px;text-transform:uppercase;color:{{ $muted }};border-bottom:1px solid {{ $line }};">Položka</th>
                <th align="center" style="padding:10px 8px;font-size:11px;font-weight:normal;letter-spacing:1.5px;text-transform:uppercase;color:{{ $muted }};border-bottom:1px solid {{ $line }};">Ks</th>
                <th align="right" style="padding:10px 8px;font-size:11px;font-weight:normal;letter-spacing:1.5px;text-transform:uppercase;color:{{ $muted }};border-bottom:1px solid {{ $line }};white-space:nowrap;">Cena / ks</th>
                <th align="right" style="padding:10px 0;font-size:11px;font-weight:normal;letter-spacing:1.5px;text-transform:uppercase;color:{{ $muted }};border-bottom:1px solid {{ $line }};">Celkem</th>
              </tr>
              @foreach ($i->items as $item)
                <tr>
                  <td style="padding:12px 0;border-bottom:1px solid {{ $line }};">
                    @if ($item->collection_name)<span style="font-size:11px;letter-spacing:1.5px;text-transform:uppercase;color:{{ $brass }};">{{ $item->collection_name }}</span><br>@endif
                    <b style="font-weight:600;">{{ $item->product_name }}</b>
                    @if ($item->option_label)<br><span style="color:{{ $muted }};">{{ $item->option_label }}</span>@endif
                  </td>
                  <td align="center" style="padding:12px 8px;border-bottom:1px solid {{ $line }};">{{ $item->quantity }}</td>
                  <td align="right" style="padding:12px 8px;border-bottom:1px solid {{ $line }};white-space:nowrap;">{{ Fmt::price($item->unit_price) }}@if ($item->rrp && $item->rrp > $item->unit_price)<br><s style="color:{{ $muted }};font-size:12px;">{{ Fmt::price($item->rrp) }}</s>@endif</td>
                  <td align="right" style="padding:12px 0;border-bottom:1px solid {{ $line }};white-space:nowrap;"><b style="font-weight:600;">{{ Fmt::price($item->line_total) }}</b></td>
                </tr>
              @endforeach
            </table>
          </td>
        </tr>

        {{-- Součty --}}
        <tr>
          <td style="padding:12px 32px 24px;">
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="{{ $font }}font-size:14px;line-height:1.5;color:{{ $ink }};">
              <tr><td style="padding:4px 0;color:{{ $muted }};">Zboží</td><td align="right" style="padding:4px 0;white-space:nowrap;">{{ Fmt::price($i->items_total) }}</td></tr>
              @if ($saving > 0)
                <tr><td style="padding:4px 0;color:{{ $olive }};">Úspora proti doporučené ceně ({{ Fmt::price($i->rrp_total) }})</td><td align="right" style="padding:4px 0;color:{{ $olive }};white-space:nowrap;">−{{ Fmt::price($saving) }}</td></tr>
              @endif
              <tr><td style="padding:4px 0;color:{{ $muted }};">Doprava – {{ $i->delivery_label }}</td><td align="right" style="padding:4px 0;white-space:nowrap;">{{ $i->delivery_price ? Fmt::price($i->delivery_price) : 'zdarma' }}</td></tr>
              <tr><td style="padding:12px 0 0;border-top:1px solid {{ $ink }};{{ $serif }}font-size:20px;">Celkem</td><td align="right" style="padding:12px 0 0;border-top:1px solid {{ $ink }};{{ $serif }}font-size:22px;white-space:nowrap;">{{ Fmt::price($i->total) }}</td></tr>
            </table>
          </td>
        </tr>

        @if ($adminUrl)
          <tr>
            <td style="padding:0 32px 28px;">
              <table role="presentation" cellpadding="0" cellspacing="0" border="0"><tr>
                <td style="background:{{ $ink }};"><a href="{{ $adminUrl }}" style="display:inline-block;padding:14px 22px;{{ $font }}font-size:12px;letter-spacing:2px;text-transform:uppercase;color:#f6f8f2;text-decoration:none;">Otevřít v administraci</a></td>
              </tr></table>
            </td>
          </tr>
        @endif

        <tr>
          <td style="padding:18px 32px;border-top:1px solid {{ $line }};{{ $font }}font-size:12px;line-height:1.5;color:{{ $muted }};">
            Odpovězte na tento e-mail – odpověď půjde zákazníkovi ({{ $i->email }}).<br>
            Poptávka je uložená v&nbsp;databázi e-shopu {{ config('shop.name') }}. Zákazník zatím nic neplatil.
          </td>
        </tr>
      </table>
    </td>
  </tr>
</table>
</body>
</html>
