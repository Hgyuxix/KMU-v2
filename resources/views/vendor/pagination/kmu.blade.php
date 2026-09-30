@if ($paginator->hasPages())
    <nav class="kmu-pagination" aria-label="Navigasi halaman">
        <div class="kmu-pagination-summary">
            Menampilkan <strong>{{ $paginator->firstItem() }}</strong>–<strong>{{ $paginator->lastItem() }}</strong>
            dari <strong>{{ $paginator->total() }}</strong> hasil
        </div>

        <div class="kmu-pagination-links">
            {{-- Previous Page --}}
            @if ($paginator->onFirstPage())
                <span class="kmu-page is-disabled" aria-disabled="true" aria-label="Halaman sebelumnya">‹</span>
            @else
                <a class="kmu-page" href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="Halaman sebelumnya">‹</a>
            @endif

            {{-- Pagination Elements --}}
            @foreach ($elements as $element)
                {{-- "Three Dots" Separator --}}
                @if (is_string($element))
                    <span class="kmu-page is-ellipsis" aria-hidden="true">{{ $element }}</span>
                @endif

                {{-- Array Of Links --}}
                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <span class="kmu-page is-current" aria-current="page">{{ $page }}</span>
                        @else
                            <a class="kmu-page" href="{{ $url }}" aria-label="Halaman {{ $page }}">{{ $page }}</a>
                        @endif
                    @endforeach
                @endif
            @endforeach

            {{-- Next Page --}}
            @if ($paginator->hasMorePages())
                <a class="kmu-page" href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="Halaman berikutnya">›</a>
            @else
                <span class="kmu-page is-disabled" aria-disabled="true" aria-label="Halaman berikutnya">›</span>
            @endif
        </div>
    </nav>
@endif
