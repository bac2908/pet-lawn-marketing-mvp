@props(['paginator', 'label'])
@if ($paginator->hasPages())
    <nav class="dashboard-pagination care-pagination" aria-label="{{ $label }}">
        @if ($paginator->onFirstPage())
            <span class="page-disabled" aria-disabled="true">← Trước</span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" rel="prev">← Trước</a>
        @endif
        <span class="page-current">Trang {{ $paginator->currentPage() }} / {{ $paginator->lastPage() }}</span>
        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" rel="next">Sau →</a>
        @else
            <span class="page-disabled" aria-disabled="true">Sau →</span>
        @endif
    </nav>
@endif
