@if ($items->hasPages())
    <nav class="pagination" aria-label="Halaman daftar">
        @if ($items->previousPageUrl())<a class="btn" href="{{ $items->previousPageUrl() }}">Sebelumnya</a>@endif
        <span>Halaman {{ $items->currentPage() }} dari {{ $items->lastPage() }}</span>
        @if ($items->nextPageUrl())<a class="btn" href="{{ $items->nextPageUrl() }}">Berikutnya</a>@endif
    </nav>
@endif