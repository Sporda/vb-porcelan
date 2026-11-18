{{-- Stránkování administrace (česky). $paginator, $elements dodává Laravel. --}}
<div class="pager">
    <p class="pager-info">
        @if ($paginator->total() > 0)
            {{ $paginator->firstItem() }}–{{ $paginator->lastItem() }} z {{ $paginator->total() }}
        @endif
    </p>

    @if ($paginator->hasPages())
        <nav class="pager-nav" aria-label="Stránkování">
            @if ($paginator->onFirstPage())
                <span class="pager-btn is-disabled" aria-hidden="true"><svg class="ic"><use href="#i-chev-l"/></svg></span>
            @else
                <a class="pager-btn" href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="Předchozí strana"><svg class="ic" aria-hidden="true"><use href="#i-chev-l"/></svg></a>
            @endif

            @foreach ($elements as $element)
                @if (is_string($element))
                    <span class="pager-gap" aria-hidden="true">…</span>
                @endif

                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <span class="pager-btn is-current" aria-current="page"><span class="sr-only">Strana </span>{{ $page }}</span>
                        @else
                            <a class="pager-btn" href="{{ $url }}" aria-label="Strana {{ $page }}">{{ $page }}</a>
                        @endif
                    @endforeach
                @endif
            @endforeach

            @if ($paginator->hasMorePages())
                <a class="pager-btn" href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="Další strana"><svg class="ic" aria-hidden="true"><use href="#i-chev-r"/></svg></a>
            @else
                <span class="pager-btn is-disabled" aria-hidden="true"><svg class="ic"><use href="#i-chev-r"/></svg></span>
            @endif
        </nav>
    @endif
</div>
