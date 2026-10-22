@if ($featured->isNotEmpty())
@php
    $n = $featured->count();
    // When there are fewer than four products, a wide tile fills the rest of the row so the grid never looks empty.
    $fill = $n < 4 ? [1 => 'lg:col-span-3', 2 => 'lg:col-span-2', 3 => 'lg:col-span-1'][$n] : null;
@endphp
<section class="py-20" data-reveal><div class="container-x">
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <p class="eyebrow">{{ __('site.featured') }}</p>
            <h2 class="section-title">{{ \App\Support\Lang::pick($content['title'] ?? '') ?: __('site.products') }}</h2>
            <span class="bar" aria-hidden="true"></span>
        </div>
        <a class="font-semibold text-industrial underline underline-offset-4 hover:text-navy" href="{{ $lr('catalog.index') }}">{{ __('site.all_products') }} →</a>
    </div>

    <div class="mt-10 grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
        @foreach ($featured as $product)@include('catalog._card', ['product' => $product])@endforeach

        @if ($fill)
            <a href="{{ $lr('catalog.index') }}" class="group relative isolate flex flex-col justify-between overflow-hidden rounded-panel bg-gradient-to-br from-navy-deep via-navy to-industrial p-8 text-white shadow-card transition hover:shadow-lift sm:col-span-2 {{ $fill }} motion-reduce:transition-none">
                <span class="bg-grid absolute inset-0 -z-10 opacity-50" aria-hidden="true"></span>
                <span>
                    <span class="eyebrow !text-amber">{{ __('site.products') }}</span>
                    <span class="mt-3 block text-2xl font-extrabold tracking-tight sm:text-3xl">{{ __('site.explore_range_title') }}</span>
                    <span class="mt-3 block max-w-md leading-relaxed text-white/75">{{ __('site.explore_range_text') }}</span>
                </span>
                <span class="mt-8 inline-flex items-center gap-2 font-bold text-amber">{{ __('site.all_products') }} <span class="transition group-hover:translate-x-1 motion-reduce:transition-none" aria-hidden="true">→</span></span>
            </a>
        @endif
    </div>
</div></section>
@endif
