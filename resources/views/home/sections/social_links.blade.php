@php
    $links = collect(['facebook' => 'Facebook', 'linkedin' => 'LinkedIn', 'youtube' => 'YouTube', 'instagram' => 'Instagram'])
        ->map(fn($l, $k) => [$l, \App\Support\Settings::get('social', $k)])
        ->filter(fn($x) => $x[1]);
@endphp
@if ($links->isNotEmpty())
    <section class="pb-4">
        <div class="container-x flex flex-wrap items-center justify-center gap-3">
            <span class="text-sm font-semibold text-ink/70">{{ __('site.follow_us') }}</span>
            @foreach ($links as [$label, $url])
                <a href="{{ $url }}" target="_blank" rel="noopener"
                    class="chip font-semibold text-navy transition hover:border-navy hover:bg-navy hover:text-white">{{ $label }}</a>
            @endforeach
        </div>
    </section>
@endif
