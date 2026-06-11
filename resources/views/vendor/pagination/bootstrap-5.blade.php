@if ($paginator->hasPages())
    <nav class="pagination-nav" role="navigation" aria-label="Pagination Navigation">
        <div class="pagination-info">
            Showing <span class="font-medium">{{ $paginator->firstItem() }}</span> to <span class="font-medium">{{ $paginator->lastItem() }}</span> of <span class="font-medium">{{ $paginator->total() }}</span> results
        </div>
        <div class="pagination-buttons">
            {{-- Previous Page Link --}}
            @if ($paginator->onFirstPage())
                <span class="pagination-btn pagination-btn-disabled" aria-disabled="true" aria-label="@lang('pagination.previous')">
                    <svg class="pagination-icon" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                    </svg>
                    <span>Previous</span>
                </span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" class="pagination-btn pagination-btn-prev" rel="prev" aria-label="@lang('pagination.previous')">
                    <svg class="pagination-icon" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                    </svg>
                    <span>Previous</span>
                </a>
            @endif

            {{-- Page Number Indicator --}}
            <span class="pagination-page-indicator">
                Page <span class="font-medium">{{ $paginator->currentPage() }}</span> of <span class="font-medium">{{ $paginator->lastPage() }}</span>
            </span>

            {{-- Next Page Link --}}
            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" class="pagination-btn pagination-btn-next" rel="next" aria-label="@lang('pagination.next')">
                    <span>Next</span>
                    <svg class="pagination-icon" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                    </svg>
                </a>
            @else
                <span class="pagination-btn pagination-btn-disabled" aria-disabled="true" aria-label="@lang('pagination.next')">
                    <span>Next</span>
                    <svg class="pagination-icon" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                    </svg>
                </span>
            @endif
        </div>
    </nav>
@endif
