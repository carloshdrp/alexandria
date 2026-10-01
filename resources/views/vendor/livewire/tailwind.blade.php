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
        <nav role="navigation" aria-label="Pagination Navigation" class="flex flex-wrap items-center justify-between gap-3">
            <p class="text-sm text-stone-500">
                {!! __('Showing') !!}
                <span class="font-medium text-stone-700">{{ $paginator->firstItem() }}</span>
                {!! __('to') !!}
                <span class="font-medium text-stone-700">{{ $paginator->lastItem() }}</span>
                {!! __('of') !!}
                <span class="font-medium text-stone-700">{{ $paginator->total() }}</span>
                {!! __('results') !!}
            </p>

            <div class="join">
                {{-- Previous Page Link --}}
                @if ($paginator->onFirstPage())
                    <span class="join-item btn btn-sm btn-disabled" aria-disabled="true" aria-label="{{ __('pagination.previous') }}">
                        <x-lucide-chevron-left class="size-4"/>
                    </span>
                @else
                    <button type="button" wire:click="previousPage('{{ $paginator->getPageName() }}')" x-on:click="{{ $scrollIntoViewJsSnippet }}" wire:loading.attr="disabled" dusk="previousPage{{ $paginator->getPageName() == 'page' ? '' : '.' . $paginator->getPageName() }}.after" class="join-item btn btn-sm" aria-label="{{ __('pagination.previous') }}">
                        <x-lucide-chevron-left class="size-4"/>
                    </button>
                @endif

                {{-- Pagination Elements --}}
                @foreach ($elements as $element)
                    {{-- "Three Dots" Separator --}}
                    @if (is_string($element))
                        <span class="join-item btn btn-sm btn-disabled" aria-disabled="true">{{ $element }}</span>
                    @endif

                    {{-- Array Of Links --}}
                    @if (is_array($element))
                        @foreach ($element as $page => $url)
                            @if ($page == $paginator->currentPage())
                                <span class="join-item btn btn-sm btn-active" aria-current="page"
                                      wire:key="paginator-{{ $paginator->getPageName() }}-page{{ $page }}">{{ $page }}</span>
                            @else
                                <button type="button" wire:click="gotoPage({{ $page }}, '{{ $paginator->getPageName() }}')" x-on:click="{{ $scrollIntoViewJsSnippet }}" class="join-item btn btn-sm" aria-label="{{ __('Go to page :page', ['page' => $page]) }}"
                                        wire:key="paginator-{{ $paginator->getPageName() }}-page{{ $page }}">{{ $page }}</button>
                            @endif
                        @endforeach
                    @endif
                @endforeach

                {{-- Next Page Link --}}
                @if ($paginator->hasMorePages())
                    <button type="button" wire:click="nextPage('{{ $paginator->getPageName() }}')" x-on:click="{{ $scrollIntoViewJsSnippet }}" wire:loading.attr="disabled" dusk="nextPage{{ $paginator->getPageName() == 'page' ? '' : '.' . $paginator->getPageName() }}.after" class="join-item btn btn-sm" aria-label="{{ __('pagination.next') }}">
                        <x-lucide-chevron-right class="size-4"/>
                    </button>
                @else
                    <span class="join-item btn btn-sm btn-disabled" aria-disabled="true" aria-label="{{ __('pagination.next') }}">
                        <x-lucide-chevron-right class="size-4"/>
                    </span>
                @endif
            </div>
        </nav>
    @endif
</div>
