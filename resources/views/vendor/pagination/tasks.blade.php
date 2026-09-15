@if ($paginator->hasPages())
    <nav role="navigation" aria-label="{{ __('Pagination Navigation') }}" class="flex items-center justify-between gap-3">
        <div class="flex w-full items-center justify-between gap-2 sm:hidden">
            @if ($paginator->onFirstPage())
                <span class="inline-flex h-9 items-center rounded-md border border-border bg-muted px-3 text-xs font-medium text-muted-foreground opacity-60 dark:border-zinc-800 dark:bg-black" aria-disabled="true">
                    {{ __('pagination.previous') }}
                </span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="inline-flex h-9 items-center rounded-md border border-input bg-background px-3 text-xs font-medium text-foreground shadow-sm transition-colors hover:bg-accent focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring dark:border-zinc-800 dark:bg-black dark:hover:bg-zinc-900">
                    {{ __('pagination.previous') }}
                </a>
            @endif

            <span class="text-xs text-muted-foreground">{{ $paginator->currentPage() }} / {{ $paginator->lastPage() }}</span>

            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="inline-flex h-9 items-center rounded-md border border-input bg-background px-3 text-xs font-medium text-foreground shadow-sm transition-colors hover:bg-accent focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring dark:border-zinc-800 dark:bg-black dark:hover:bg-zinc-900">
                    {{ __('pagination.next') }}
                </a>
            @else
                <span class="inline-flex h-9 items-center rounded-md border border-border bg-muted px-3 text-xs font-medium text-muted-foreground opacity-60 dark:border-zinc-800 dark:bg-black" aria-disabled="true">
                    {{ __('pagination.next') }}
                </span>
            @endif
        </div>

        <div class="hidden w-full items-center justify-between gap-4 sm:flex">
            <p class="text-xs text-muted-foreground">
                @if ($paginator->firstItem())
                    Exibindo <span class="font-medium text-foreground">{{ $paginator->firstItem() }}–{{ $paginator->lastItem() }}</span> de <span class="font-medium text-foreground">{{ $paginator->total() }}</span>
                @else
                    Nenhum resultado
                @endif
            </p>

            <div class="inline-flex items-center rounded-md border border-input bg-background p-0.5 shadow-sm dark:border-zinc-800 dark:bg-black">
                @if ($paginator->onFirstPage())
                    <span class="flex h-7 w-7 items-center justify-center rounded-sm text-muted-foreground opacity-45" aria-disabled="true" aria-label="{{ __('pagination.previous') }}">
                        <i class="ph ph-caret-left text-sm" aria-hidden="true"></i>
                    </span>
                @else
                    <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="flex h-7 w-7 items-center justify-center rounded-sm text-muted-foreground transition-colors hover:bg-accent hover:text-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring dark:hover:bg-zinc-900" aria-label="{{ __('pagination.previous') }}">
                        <i class="ph ph-caret-left text-sm" aria-hidden="true"></i>
                    </a>
                @endif

                @foreach ($elements as $element)
                    @if (is_string($element))
                        <span class="flex h-7 min-w-7 items-center justify-center px-1 text-xs text-muted-foreground">{{ $element }}</span>
                    @endif

                    @if (is_array($element))
                        @foreach ($element as $page => $url)
                            @if ($page == $paginator->currentPage())
                                <span aria-current="page" class="flex h-7 min-w-7 items-center justify-center rounded-sm bg-primary px-2 text-xs font-medium text-primary-foreground dark:bg-zinc-100 dark:text-zinc-950">{{ $page }}</span>
                            @else
                                <a href="{{ $url }}" class="flex h-7 min-w-7 items-center justify-center rounded-sm px-2 text-xs font-medium text-muted-foreground transition-colors hover:bg-accent hover:text-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring dark:hover:bg-zinc-900" aria-label="{{ __('Go to page :page', ['page' => $page]) }}">{{ $page }}</a>
                            @endif
                        @endforeach
                    @endif
                @endforeach

                @if ($paginator->hasMorePages())
                    <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="flex h-7 w-7 items-center justify-center rounded-sm text-muted-foreground transition-colors hover:bg-accent hover:text-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring dark:hover:bg-zinc-900" aria-label="{{ __('pagination.next') }}">
                        <i class="ph ph-caret-right text-sm" aria-hidden="true"></i>
                    </a>
                @else
                    <span class="flex h-7 w-7 items-center justify-center rounded-sm text-muted-foreground opacity-45" aria-disabled="true" aria-label="{{ __('pagination.next') }}">
                        <i class="ph ph-caret-right text-sm" aria-hidden="true"></i>
                    </span>
                @endif
            </div>
        </div>
    </nav>
@endif
