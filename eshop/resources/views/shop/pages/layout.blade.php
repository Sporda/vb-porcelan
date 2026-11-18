@extends('layouts.shop')

@section('content')
<div class="wrap">
  <nav class="crumbs" aria-label="Drobečková navigace">
    <a href="{{ route('home') }}">Domů</a><svg class="ic" aria-hidden="true"><use href="#i-chev-r"/></svg>
    <span aria-current="page">@yield('title')</span>
  </nav>
  <header class="page-head">
    <p class="label">@yield('page_label', 'Informace')</p>
    <h1>@yield('page_h1')</h1>
    @hasSection('page_lead')<p class="lead">@yield('page_lead')</p>@endif
  </header>
  @hasSection('draft')
    <p class="notice notice-draft"><svg class="ic" aria-hidden="true"><use href="#i-info"/></svg><span>@yield('draft')</span></p>
  @endif
  <div class="info-grid page-body">
    <div class="prose">
      @yield('page_body')
    </div>
    <aside class="info-aside">
      @yield('page_aside')
      <nav class="info-nav" aria-label="Informace pro zákazníky">
        <p class="label">Informace</p>
        <ul>
          @foreach ([
              'pages.why' => 'Proč tak levně',
              'pages.about' => 'O obchodě',
              'pages.delivery' => 'Doprava a platba',
              'pages.returns' => 'Reklamace a vrácení',
              'pages.terms' => 'Obchodní podmínky',
              'pages.privacy' => 'Ochrana osobních údajů',
              'pages.contact' => 'Kontakt',
          ] as $name => $label)
            <li><a href="{{ route($name) }}" @if (request()->routeIs($name)) aria-current="page" @endif>{{ $label }}</a></li>
          @endforeach
        </ul>
      </nav>
    </aside>
  </div>
</div>
@endsection
