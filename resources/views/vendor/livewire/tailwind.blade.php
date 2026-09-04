@php
if (! isset($scrollTo)) {
    $scrollTo = 'body';
}

$scrollIntoViewJsSnippet = ($scrollTo !== false)
    ? <<<JS
       (\$el.closest('{$scrollTo}') || document.querySelector('{$scrollTo}')).scrollIntoView()
    JS
    : '';
@endphp

<div>
    @if ($paginator->hasPages())
        <nav role="navigation" aria-label="Pagination Navigation" class="flex items-center justify-between">
            <div class="flex justify-between flex-1 sm:hidden">
                <span>
                    @if ($paginator->onFirstPage())
                        <span class="inline-flex h-8 items-center px-4 text-sm font-medium text-zinc-400 bg-white border border-zinc-200 cursor-not-allowed rounded dark:text-zinc-500 dark:bg-zinc-800 dark:border-zinc-700">
                            {!! __('pagination.previous') !!}
                        </span>
                    @else
                        <button type="button" wire:click="previousPage('{{ $paginator->getPageName() }}')" x-on:click="{{ $scrollIntoViewJsSnippet }}" wire:loading.attr="disabled" dusk="previousPage{{ $paginator->getPageName() == 'page' ? '' : '.' . $paginator->getPageName() }}.before" class="inline-flex h-8 items-center px-4 text-sm font-medium text-zinc-700 bg-white border border-zinc-200 rounded hover:border-zinc-300 focus:outline-none focus:ring-2 focus:ring-primary/10 focus:border-primary transition-colors dark:bg-zinc-800 dark:border-zinc-700 dark:text-zinc-300 dark:hover:border-zinc-600">
                            {!! __('pagination.previous') !!}
                        </button>
                    @endif
                </span>

                <span>
                    @if ($paginator->hasMorePages())
                        <button type="button" wire:click="nextPage('{{ $paginator->getPageName() }}')" x-on:click="{{ $scrollIntoViewJsSnippet }}" wire:loading.attr="disabled" dusk="nextPage{{ $paginator->getPageName() == 'page' ? '' : '.' . $paginator->getPageName() }}.before" class="inline-flex h-8 items-center px-4 text-sm font-medium text-zinc-700 bg-white border border-zinc-200 rounded hover:border-zinc-300 focus:outline-none focus:ring-2 focus:ring-primary/10 focus:border-primary transition-colors dark:bg-zinc-800 dark:border-zinc-700 dark:text-zinc-300 dark:hover:border-zinc-600">
                            {!! __('pagination.next') !!}
                        </button>
                    @else
                        <span class="inline-flex h-8 items-center px-4 text-sm font-medium text-zinc-400 bg-white border border-zinc-200 cursor-not-allowed rounded dark:text-zinc-500 dark:bg-zinc-800 dark:border-zinc-700">
                            {!! __('pagination.next') !!}
                        </span>
                    @endif
                </span>
            </div>

            <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                <div>
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">
                        <span>{!! __('Showing') !!}</span>
                        <span class="font-medium text-zinc-700 dark:text-zinc-300">{{ $paginator->firstItem() }}</span>
                        <span>{!! __('to') !!}</span>
                        <span class="font-medium text-zinc-700 dark:text-zinc-300">{{ $paginator->lastItem() }}</span>
                        <span>{!! __('of') !!}</span>
                        <span class="font-medium text-zinc-700 dark:text-zinc-300">{{ $paginator->total() }}</span>
                        <span>{!! __('results') !!}</span>
                    </p>
                </div>

                <div>
                    <span class="inline-flex rtl:flex-row-reverse">
                        <span>
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
                                <button type="button" wire:click="previousPage('{{ $paginator->getPageName() }}')" x-on:click="{{ $scrollIntoViewJsSnippet }}" dusk="previousPage{{ $paginator->getPageName() == 'page' ? '' : '.' . $paginator->getPageName() }}.after" class="flex h-8 w-8 items-center justify-center text-zinc-500 bg-white border border-zinc-200 rounded-l hover:border-zinc-300 hover:text-zinc-700 focus:z-10 focus:outline-none focus:ring-2 focus:ring-primary/10 focus:border-primary transition-colors dark:bg-zinc-800 dark:border-zinc-700 dark:text-zinc-400 dark:hover:border-zinc-600 dark:hover:text-zinc-200" aria-label="{{ __('pagination.previous') }}">
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                                    </svg>
                                </button>
                            @endif
                        </span>

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
                                    <span wire:key="paginator-{{ $paginator->getPageName() }}-page{{ $page }}">
                                        @if ($page == $paginator->currentPage())
                                            <span aria-current="page">
                                                <span class="flex h-8 min-w-8 -ml-px items-center justify-center px-2 text-sm font-medium text-white bg-primary border border-primary cursor-default">{{ $page }}</span>
                                            </span>
                                        @else
                                            <button type="button" wire:click="gotoPage({{ $page }}, '{{ $paginator->getPageName() }}')" x-on:click="{{ $scrollIntoViewJsSnippet }}" class="flex h-8 min-w-8 -ml-px items-center justify-center px-2 text-sm font-medium text-zinc-600 bg-white border border-zinc-200 hover:border-zinc-300 hover:text-zinc-800 focus:z-10 focus:outline-none focus:ring-2 focus:ring-primary/10 focus:border-primary transition-colors dark:bg-zinc-800 dark:border-zinc-700 dark:text-zinc-300 dark:hover:border-zinc-600 dark:hover:text-white" aria-label="{{ __('Go to page :page', ['page' => $page]) }}">
                                                {{ $page }}
                                            </button>
                                        @endif
                                    </span>
                                @endforeach
                            @endif
                        @endforeach

                        <span>
                            {{-- Next Page Link --}}
                            @if ($paginator->hasMorePages())
                                <button type="button" wire:click="nextPage('{{ $paginator->getPageName() }}')" x-on:click="{{ $scrollIntoViewJsSnippet }}" dusk="nextPage{{ $paginator->getPageName() == 'page' ? '' : '.' . $paginator->getPageName() }}.after" class="flex h-8 w-8 -ml-px items-center justify-center text-zinc-500 bg-white border border-zinc-200 rounded-r hover:border-zinc-300 hover:text-zinc-700 focus:z-10 focus:outline-none focus:ring-2 focus:ring-primary/10 focus:border-primary transition-colors dark:bg-zinc-800 dark:border-zinc-700 dark:text-zinc-400 dark:hover:border-zinc-600 dark:hover:text-zinc-200" aria-label="{{ __('pagination.next') }}">
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                                    </svg>
                                </button>
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
                    </span>
                </div>
            </div>
        </nav>
    @endif
</div>
