@if ($paginator->hasPages())
  <nav class="pager" role="navigation" aria-label="Stránkování">
    @if ($paginator->onFirstPage())
      <span class="pager-step is-disabled" aria-disabled="true"><svg class="ic" aria-hidden="true"><use href="#i-chev-l"/></svg><span>Předchozí</span></span>
    @else
      <a class="pager-step" href="{{ $paginator->previousPageUrl() }}" rel="prev"><svg class="ic" aria-hidden="true"><use href="#i-chev-l"/></svg><span>Předchozí</span></a>
    @endif

    @if (isset($elements))
      <ul class="pager-pages">
        @foreach ($elements as $element)
          @if (is_string($element))
            <li><span class="pager-gap" aria-hidden="true">…</span></li>
          @endif
          @if (is_array($element))
            @foreach ($element as $page => $url)
              @if ($page == $paginator->currentPage())
                <li><span class="pager-page is-current" aria-current="page"><span class="sr-only">Strana </span>{{ $page }}</span></li>
              @else
                <li><a class="pager-page" href="{{ $url }}" aria-label="Strana {{ $page }}">{{ $page }}</a></li>
              @endif
            @endforeach
          @endif
        @endforeach
      </ul>
    @endif

    @if ($paginator->hasMorePages())
      <a class="pager-step" href="{{ $paginator->nextPageUrl() }}" rel="next"><span>Další</span><svg class="ic" aria-hidden="true"><use href="#i-chev-r"/></svg></a>
    @else
      <span class="pager-step is-disabled" aria-disabled="true"><span>Další</span><svg class="ic" aria-hidden="true"><use href="#i-chev-r"/></svg></span>
    @endif
  </nav>
@endif
