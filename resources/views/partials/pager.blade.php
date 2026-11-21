@php
    /** Paginasi bergaya desain: panah, 1 2 3 … terakhir. Memakai paginator bawaan Laravel. */
    $cur = $paginator->currentPage();
    $last = $paginator->lastPage();
    $pages = collect([1, $last, $cur - 1, $cur, $cur + 1, 2, 3])
        ->filter(fn ($p) => $p >= 1 && $p <= $last)->unique()->sort()->values();
    if ($cur > 3) { $pages = $pages->reject(fn ($p) => $p === 2 || $p === 3)->values(); if ($cur - 1 > 1) { $pages = $pages->push($cur - 1)->unique()->sort()->values(); } }
    $prevUrl = $paginator->previousPageUrl();
    $nextUrl = $paginator->nextPageUrl();
@endphp
@if($last > 1)
<nav class="sh-pager" aria-label="Pagination">
    @if($prevUrl)<a href="{{ $prevUrl }}" class="sh-pg" aria-label="Previous"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 12H5m6-6-6 6 6 6"/></svg></a>
    @else<span class="sh-pg sh-pg-off" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 12H5m6-6-6 6 6 6"/></svg></span>@endif

    @php $prev = 0; @endphp
    @foreach($pages as $p)
        @if($prev && $p - $prev > 1)<span class="sh-pg sh-pg-dots">…</span>@endif
        @if($p === $cur)<span class="sh-pg sh-pg-on" aria-current="page">{{ $p }}</span>
        @else<a href="{{ $paginator->url($p) }}" class="sh-pg">{{ $p }}</a>@endif
        @php $prev = $p; @endphp
    @endforeach

    @if($nextUrl)<a href="{{ $nextUrl }}" class="sh-pg" aria-label="Next"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14m-6-6 6 6-6 6"/></svg></a>
    @else<span class="sh-pg sh-pg-off" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14m-6-6 6 6-6 6"/></svg></span>@endif
</nav>
@endif
