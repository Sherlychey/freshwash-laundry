@if ($paginator->hasPages())
    <nav aria-label="Navigasi halaman">
        <div class="pagination-list">
            @if ($paginator->onFirstPage())
                <span class="disabled" aria-disabled="true">‹</span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="Sebelumnya">‹</a>
            @endif
            @foreach ($elements as $element)
                @if (is_string($element))
                    <span class="dots" aria-disabled="true">{{ $element }}</span>
                @endif
                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <span class="current" aria-current="page">{{ $page }}</span>
                        @else
                            <a href="{{ $url }}" aria-label="Halaman {{ $page }}">{{ $page }}</a>
                        @endif
                    @endforeach
                @endif
            @endforeach
            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="Berikutnya">›</a>
            @else
                <span class="disabled" aria-disabled="true">›</span>
            @endif
        </div>
    </nav>
@endif
