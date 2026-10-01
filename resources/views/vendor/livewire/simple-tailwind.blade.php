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
        <nav role="navigation" aria-label="Pagination Navigation" class="join">
            {{-- Previous Page Link --}}
            @if ($paginator->onFirstPage())
                <span class="join-item btn btn-sm btn-disabled" aria-disabled="true">{!! __('pagination.previous') !!}</span>
            @elseif (method_exists($paginator, 'getCursorName'))
                {{-- On an empty page the previous cursor is null, so fall back to the current cursor (reloads the same page, mirroring Laravel's null page URL) --}}
                @php($previousCursor = $paginator->previousCursor() ?? $paginator->cursor())
                <button type="button" dusk="previousPage" wire:key="cursor-{{ $paginator->getCursorName() }}-{{ $previousCursor?->encode() }}" wire:click="setPage('{{ $previousCursor?->encode() }}','{{ $paginator->getCursorName() }}')" x-on:click="{{ $scrollIntoViewJsSnippet }}" wire:loading.attr="disabled" class="join-item btn btn-sm">
                    {!! __('pagination.previous') !!}
                </button>
            @else
                <button type="button" wire:click="previousPage('{{ $paginator->getPageName() }}')" x-on:click="{{ $scrollIntoViewJsSnippet }}" wire:loading.attr="disabled" dusk="previousPage{{ $paginator->getPageName() == 'page' ? '' : '.' . $paginator->getPageName() }}" class="join-item btn btn-sm">
                    {!! __('pagination.previous') !!}
                </button>
            @endif

            {{-- Next Page Link --}}
            @if (! $paginator->hasMorePages())
                <span class="join-item btn btn-sm btn-disabled" aria-disabled="true">{!! __('pagination.next') !!}</span>
            @elseif (method_exists($paginator, 'getCursorName'))
                {{-- On an empty page the next cursor is null, so fall back to the current cursor (reloads the same page, mirroring Laravel's null page URL) --}}
                @php($nextCursor = $paginator->nextCursor() ?? $paginator->cursor())
                <button type="button" dusk="nextPage" wire:key="cursor-{{ $paginator->getCursorName() }}-{{ $nextCursor?->encode() }}" wire:click="setPage('{{ $nextCursor?->encode() }}','{{ $paginator->getCursorName() }}')" x-on:click="{{ $scrollIntoViewJsSnippet }}" wire:loading.attr="disabled" class="join-item btn btn-sm">
                    {!! __('pagination.next') !!}
                </button>
            @else
                <button type="button" wire:click="nextPage('{{ $paginator->getPageName() }}')" x-on:click="{{ $scrollIntoViewJsSnippet }}" wire:loading.attr="disabled" dusk="nextPage{{ $paginator->getPageName() == 'page' ? '' : '.' . $paginator->getPageName() }}" class="join-item btn btn-sm">
                    {!! __('pagination.next') !!}
                </button>
            @endif
        </nav>
    @endif
</div>
