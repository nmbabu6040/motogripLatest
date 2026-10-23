@php
    $name = \App\Support\Settings::get('brand', 'site_name', config('app.name'));
    $pick = fn($k) => \App\Support\Lang::pick($content[$k] ?? '');
    $title = $pick('title') ?: \App\Support\Settings::get('brand', 'tagline') ?: $name;
    $hero = \App\Support\Settings::get('brand', 'hero_image_url');
    $cats = isset($categories) ? $categories : collect();
@endphp
<section class="relative isolate overflow-hidden bg-navy-deep text-white">
    {{-- background: gradient + faint grid + two soft glows --}}
    <div class="absolute inset-0 -z-10 bg-gradient-to-br from-navy-deep via-navy to-industrial"></div>
    <div class="bg-grid absolute inset-0 -z-10"></div>
    <div class="absolute -right-20 top-0 -z-10 h-[30rem] w-[30rem] rounded-full bg-amber/20 blur-3xl" aria-hidden="true">
    </div>
    <div class="absolute -bottom-40 -left-20 -z-10 h-[26rem] w-[26rem] rounded-full bg-industrial/70 blur-3xl"
        aria-hidden="true"></div>

    <div class="container-x grid items-center gap-12 py-16 lg:grid-cols-[1.1fr_0.9fr] lg:py-24">
        <div>
            <p
                class="inline-flex items-center gap-2 rounded-full border border-white/20 bg-white/5 px-3.5 py-1.5 text-xs font-bold uppercase tracking-[0.18em] text-amber">
                <span class="h-1.5 w-1.5 rounded-full bg-amber" aria-hidden="true"></span>{{ $name }}
            </p>
            <h1 class="mt-6 text-display font-extrabold">{{ $title }}</h1>
            @if ($body = $pick('body'))
                <p class="mt-6 max-w-xl text-lg leading-relaxed text-white/80">{{ $body }}</p>
            @endif

            <div class="mt-9 flex flex-wrap items-center gap-3">
                <a href="{{ $lr('catalog.index') }}" class="btn btn-primary">{{ __('site.explore_products') }} <span
                        aria-hidden="true">→</span></a>
                <a href="{{ $lr('dealer.apply') }}" class="btn btn-light">{{ __('site.become_dealer') }}</a>
                <a href="{{ $lr('contact.create') }}"
                    class="font-semibold btn btn-primary">{{ __('site.contact_sales') }}</a>
            </div>

            @if ($cats->isNotEmpty())
                <div class="mt-12 border-t border-white/10 pt-6">
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-white/50">
                        {{ __('site.quick_links') }}</p>
                    <ul class="mt-3 flex flex-wrap gap-2">
                        @foreach ($cats->take(5) as $c)
                            <li><a href="{{ $lr('catalog.index') }}?category={{ $c->slug }}"
                                    class="inline-block rounded-full border border-white/20 px-3.5 py-1.5 text-sm transition hover:border-amber hover:text-amber">{{ $c->name }}</a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>

        @if ($hero)
            <div class="relative mx-auto w-full max-w-md lg:max-w-none">
                <div class="absolute -inset-4 rounded-[1.75rem] bg-gradient-to-tr from-amber/40 via-transparent to-industrial/50 blur-2xl"
                    aria-hidden="true"></div>
                <div class="float-slow relative">
                    <img src="{{ $hero }}" alt="" width="640" height="800" fetchpriority="high"
                        class="relative mx-auto aspect-[4/5] max-h-[36rem] w-full rounded-panel object-cover shadow-lift ring-1 ring-white/20">
                    @if (($productCount ?? 0) > 0)
                        <div
                            class="absolute -bottom-5 left-4 rounded-panel border border-white/20 bg-navy-deep/80 px-5 py-3 backdrop-blur sm:-left-6">
                            <p class="text-2xl font-extrabold leading-none text-amber">{{ $productCount }}</p>
                            <p class="mt-1 text-xs font-semibold uppercase tracking-wider text-white/70">
                                {{ trans_choice('site.n_products', $productCount) }} · {{ $categoryCount }}
                                {{ trans_choice('site.n_categories', $categoryCount) }}</p>
                        </div>
                    @endif
                </div>
            </div>
        @endif
    </div>

    {{-- scrolling strip of real category names --}}
    @if ($cats->isNotEmpty())
        <div class="marquee overflow-hidden border-t border-white/10 bg-navy-deep/60 py-4" aria-hidden="true">
            <div
                class="marquee-track flex w-max gap-10 whitespace-nowrap text-sm font-semibold uppercase tracking-[0.18em] text-white/60">
                @foreach (range(1, 4) as $_)
                    @foreach ($cats as $c)
                        <span class="flex items-center gap-10">{{ $c->name }}<span
                                class="text-amber">◆</span></span>
                    @endforeach
                @endforeach
            </div>
        </div>
    @endif
</section>
