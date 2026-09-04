@if ($paginator->hasPages())
    <nav role="navigation" aria-label="{{ __('Pagination Navigation') }}">

        <div class="flex gap-2 items-center justify-between sm:hidden">

            @if ($paginator->onFirstPage())
                <span class="inline-flex h-8 items-center px-4 text-sm font-medium text-zinc-400 bg-white border border-zinc-200 cursor-not-allowed rounded dark:text-zinc-500 dark:bg-zinc-800 dark:border-zinc-700">
                    {!! __('pagination.previous') !!}
                </span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="inline-flex h-8 items-center px-4 text-sm font-medium text-zinc-700 bg-white border border-zinc-200 rounded hover:border-zinc-300 focus:outline-none focus:ring-2 focus:ring-primary/10 focus:border-primary transition-colors dark:bg-zinc-800 dark:border-zinc-700 dark:text-zinc-300 dark:hover:border-zinc-600">
                    {!! __('pagination.previous') !!}
                </a>
            @endif

            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="inline-flex h-8 items-center px-4 text-sm font-medium text-zinc-700 bg-white border border-zinc-200 rounded hover:border-zinc-300 focus:outline-none focus:ring-2 focus:ring-primary/10 focus:border-primary transition-colors dark:bg-zinc-800 dark:border-zinc-700 dark:text-zinc-300 dark:hover:border-zinc-600">
                    {!! __('pagination.next') !!}
                </a>
            @else
                <span class="inline-flex h-8 items-center px-4 text-sm font-medium text-zinc-400 bg-white border border-zinc-200 cursor-not-allowed rounded dark:text-zinc-500 dark:bg-zinc-800 dark:border-zinc-700">
                    {!! __('pagination.next') !!}
                </span>
            @endif

        </div>

        <div class="hidden sm:flex-1 sm:flex sm:gap-2 sm:items-center sm:justify-between">

            <div>
                <p class="text-sm text-zinc-500 dark:text-zinc-400">
                    {!! __('Showing') !!}
                    @if ($paginator->firstItem())
                        <span class="font-medium text-zinc-700 dark:text-zinc-300">{{ $paginator->firstItem() }}</span>
                        {!! __('to') !!}
                        <span class="font-medium text-zinc-700 dark:text-zinc-300">{{ $paginator->lastItem() }}</span>
                    @else
                        {{ $paginator->count() }}
                    @endif
                    {!! __('of') !!}
                    <span class="font-medium text-zinc-700 dark:text-zinc-300">{{ $paginator->total() }}</span>
                    {!! __('results') !!}
                </p>
            </div>

            <div>
                <span class="inline-flex rtl:flex-row-reverse">

                    {{-- Previous Page Link --}}
                    @if ($paginator->onFirstPage())
                        <span aria-disabled="true" aria-label="{{ __('pagination.previous') }}">
                            <span class="flex h-8 w-8 items-center justify-center text-zinc-300 bg-white border border-zinc-200 cursor-not-allowed rounded-l dark:bg-zinc-800 dark:border-zinc-700 dark:text-zinc-600" aria-hidden="true">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                                </svg>
                            </span>
                        </span>
                    @else
                        <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="flex h-8 w-8 items-center justify-center text-zinc-500 bg-white border border-zinc-200 rounded-l hover:border-zinc-300 hover:text-zinc-700 focus:outline-none focus:ring-2 focus:ring-primary/10 focus:border-primary transition-colors dark:bg-zinc-800 dark:border-zinc-700 dark:text-zinc-400 dark:hover:border-zinc-600 dark:hover:text-zinc-200" aria-label="{{ __('pagination.previous') }}">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                            </svg>
                        </a>
                    @endif

                    {{-- Pagination Elements --}}
                    @foreach ($elements as $element)
                        {{-- "Three Dots" Separator --}}
                        @if (is_string($element))
                            <span aria-disabled="true">
                                <span class="flex h-8 min-w-8 -ml-px items-center justify-center px-2 text-sm font-medium text-zinc-400 bg-white border border-zinc-200 cursor-default dark:bg-zinc-800 dark:border-zinc-700 dark:text-zinc-500">{{ $element }}</span>
                            </span>
                        @endif

                        {{-- Array Of Links --}}
                        @if (is_array($element))
                            @foreach ($element as $page => $url)
                                @if ($page == $paginator->currentPage())
                                    <span aria-current="page">
                                        <span class="flex h-8 min-w-8 -ml-px items-center justify-center px-2 text-sm font-medium text-white bg-primary border border-primary cursor-default">{{ $page }}</span>
                                    </span>
                                @else
                                    <a href="{{ $url }}" class="flex h-8 min-w-8 -ml-px items-center justify-center px-2 text-sm font-medium text-zinc-600 bg-white border border-zinc-200 hover:border-zinc-300 hover:text-zinc-800 focus:outline-none focus:ring-2 focus:ring-primary/10 focus:border-primary transition-colors dark:bg-zinc-800 dark:border-zinc-700 dark:text-zinc-300 dark:hover:border-zinc-600 dark:hover:text-white" aria-label="{{ __('Go to page :page', ['page' => $page]) }}">
                                        {{ $page }}
                                    </a>
                                @endif
                            @endforeach
                        @endif
                    @endforeach

                    {{-- Next Page Link --}}
                    @if ($paginator->hasMorePages())
                        <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="flex h-8 w-8 -ml-px items-center justify-center text-zinc-500 bg-white border border-zinc-200 rounded-r hover:border-zinc-300 hover:text-zinc-700 focus:outline-none focus:ring-2 focus:ring-primary/10 focus:border-primary transition-colors dark:bg-zinc-800 dark:border-zinc-700 dark:text-zinc-400 dark:hover:border-zinc-600 dark:hover:text-zinc-200" aria-label="{{ __('pagination.next') }}">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                            </svg>
                        </a>
                    @else
                        <span aria-disabled="true" aria-label="{{ __('pagination.next') }}">
                            <span class="flex h-8 w-8 -ml-px items-center justify-center text-zinc-300 bg-white border border-zinc-200 cursor-not-allowed rounded-r dark:bg-zinc-800 dark:border-zinc-700 dark:text-zinc-600" aria-hidden="true">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                                </svg>
                            </span>
                        </span>
                    @endif
                </span>
            </div>
        </div>
    </nav>
@endif
