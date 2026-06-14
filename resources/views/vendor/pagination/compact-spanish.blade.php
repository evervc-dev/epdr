@if ($paginator->hasPages())
    <nav role="navigation" aria-label="Pagination">
        <div class="inline-flex items-center gap-1">
            @if ($paginator->onFirstPage())
                <span class="inline-flex items-center justify-center h-9 min-w-9 px-3 rounded-md border border-slate-200 bg-white text-slate-300 cursor-not-allowed" aria-disabled="true">
                    &lt;
                </span>
            @else
                <a href="{{ url($paginator->previousPageUrl()) }}" rel="prev" class="inline-flex items-center justify-center h-9 min-w-9 px-3 rounded-md border border-slate-200 bg-white text-slate-700 hover:bg-slate-50 transition" aria-label="Anterior">
                    &lt;
                </a>
            @endif

            @php
                $lastPage = $paginator->lastPage();
                $pages = $lastPage <= 7 ? range(1, $lastPage) : [1, 2, 3, 4, 5, '...', $lastPage - 1, $lastPage];
            @endphp

            @foreach ($pages as $page)
                @if ($page === '...')
                    <span class="inline-flex items-center justify-center h-9 min-w-9 px-3 rounded-md border border-transparent text-slate-400 cursor-default">
                        ...
                    </span>
                @elseif ($page == $paginator->currentPage())
                    <span aria-current="page" class="inline-flex items-center justify-center h-9 min-w-9 px-3 rounded-md border border-slate-900 bg-slate-900 text-white font-semibold">
                        {{ $page }}
                    </span>
                @else
                    <a href="{{ url($paginator->url($page)) }}" class="inline-flex items-center justify-center h-9 min-w-9 px-3 rounded-md border border-slate-200 bg-white text-slate-700 hover:bg-slate-50 transition" aria-label="Ir a la página {{ $page }}">
                        {{ $page }}
                    </a>
                @endif
            @endforeach

            @if ($paginator->hasMorePages())
                <a href="{{ url($paginator->nextPageUrl()) }}" rel="next" class="inline-flex items-center justify-center h-9 min-w-9 px-3 rounded-md border border-slate-200 bg-white text-slate-700 hover:bg-slate-50 transition" aria-label="Siguiente">
                    &gt;
                </a>
            @else
                <span class="inline-flex items-center justify-center h-9 min-w-9 px-3 rounded-md border border-slate-200 bg-white text-slate-300 cursor-not-allowed" aria-disabled="true">
                    &gt;
                </span>
            @endif
        </div>
    </nav>
@endif