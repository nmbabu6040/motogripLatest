@props(['title' => null, 'description' => null, 'canonical' => null, 'image' => null, 'noindex' => false, 'type' => 'website', 'jsonld' => [], 'breadcrumbs' => []])
@php
    $logo = \App\Support\Settings::get('brand', 'logo_url');
    $ga = \App\Support\Settings::get('integrations', 'ga_measurement_id');
    $adsense = config('motogrip.features.adsense') && \App\Support\Settings::get('ads', 'enabled', false) ? \App\Support\Settings::get('ads', 'publisher_id') : null;

    $schemas = array_values(array_filter([
        ['@context' => 'https://schema.org', '@type' => 'Organization', 'name' => $siteName, 'url' => url('/'),
            'logo' => $logo ?: null, 'email' => \App\Support\Settings::get('contact', 'email') ?: null,
            'telephone' => \App\Support\Settings::get('contact', 'phone') ?: null],
        ['@context' => 'https://schema.org', '@type' => 'WebSite', 'name' => $siteName, 'url' => url('/'),
            'potentialAction' => ['@type' => 'SearchAction', 'target' => $lr('search').'?q={search_term_string}', 'query-input' => 'required name=search_term_string']],
        $breadcrumbs ? ['@context' => 'https://schema.org', '@type' => 'BreadcrumbList', 'itemListElement' => collect($breadcrumbs)->values()->map(fn ($b, $i) => ['@type' => 'ListItem', 'position' => $i + 1, 'name' => $b[0], 'item' => $b[1]])->all()] : null,
        ...$jsonld,
    ]));
    $strip = fn ($a) => array_filter($a, fn ($v) => $v !== null && $v !== '');
@endphp
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <x-seo :title="$title" :description="$description" :canonical="$canonical" :image="$image" :noindex="$noindex" :type="$type" :alternates="$alternates" />
    @if ($favicon = \App\Support\Settings::get('brand', 'favicon_url'))<link rel="icon" href="{{ $favicon }}">@endif
    @if ($v = \App\Support\Settings::get('integrations', 'search_console_token'))<meta name="google-site-verification" content="{{ $v }}">@endif
    @if ($ga)<meta name="mg-ga" content="{{ $ga }}">@endif
    @if ($adsense)<meta name="mg-adsense" content="{{ $adsense }}">@endif
    @foreach ($schemas as $schema)
        <script type="application/ld+json">{!! json_encode($strip($schema), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) !!}</script>
    @endforeach
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="flex min-h-screen flex-col bg-white" x-data="{ nav: false }">
<a href="#main" class="sr-only focus:not-sr-only focus:fixed focus:left-2 focus:top-2 focus:z-50 focus:rounded-control focus:bg-navy focus:px-4 focus:py-2 focus:text-white">{{ __('site.skip') }}</a>

@if ($bar = \App\Support\Settings::get('header', 'announcement'))
    <div class="bg-amber px-4 py-2 text-center text-sm font-semibold text-navy">{{ $bar }}</div>
@endif

{{-- ===== HEADER ===== --}}
<header class="{{ \App\Support\Settings::get('header', 'sticky', true) ? 'sticky top-0 z-40' : '' }} border-b border-white/10 bg-navy-deep/95 text-white backdrop-blur">
    <div class="container-x flex items-center gap-6 py-3">
        <a href="{{ $lr('home') }}" class="shrink-0" aria-label="{{ $siteName }}">
            @if ($logo)<span class="inline-flex items-center rounded-control bg-white px-3 py-1.5"><img src="{{ $logo }}" alt="{{ $siteName }}" class="h-8 w-auto" width="160" height="32"></span>
            @else<span class="text-xl font-extrabold tracking-tight">{{ $siteName }}</span>@endif
        </a>

        <nav class="ml-4 hidden md:block" aria-label="Main">
            <ul class="flex items-center gap-1">
                @foreach (collect($navItems)->whereNull('parent_id') as $item)
                    <li><a href="{{ $item['url'] }}" @if ($item['new_tab']) target="_blank" rel="noopener" @endif class="rounded-control px-3 py-2 text-sm font-semibold text-white/85 transition hover:bg-white/10 hover:text-white">{{ $item['label'] }}</a></li>
                @endforeach
            </ul>
        </nav>

        <form action="{{ $lr('search') }}" method="GET" role="search" class="relative ml-auto hidden lg:block" x-data="suggest()" x-on:click.outside="open = false">
            <label for="q" class="sr-only">{{ __('site.search') }}</label>
            <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-white/60" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="M21 21l-4.3-4.3"/></svg>
            <input id="q" name="q" type="search" autocomplete="off" placeholder="{{ __('site.search_placeholder') }}" x-model="q" x-on:input.debounce.250ms="fetch('{{ $lr('search.suggest') }}')"
                   class="w-64 rounded-full border border-white/15 bg-white/10 py-2 pl-9 pr-4 text-sm text-white placeholder:text-white/60 focus:bg-white/15">
            <ul x-show="open && items.length" x-cloak class="absolute left-0 right-0 mt-2 overflow-hidden rounded-panel bg-white text-ink shadow-lift">
                <template x-for="item in items" :key="item.url"><li><a :href="item.url" class="block px-4 py-2.5 text-sm hover:bg-surface" x-text="item.label"></a></li></template>
            </ul>
        </form>

        <div class="ml-auto flex items-center gap-3 text-sm lg:ml-0">
            @if (count($alternates))
                <a href="{{ app()->getLocale() === 'bn' ? $alternates['en'] : $alternates['bn'] }}" hreflang="{{ app()->getLocale() === 'bn' ? 'en' : 'bn' }}" lang="{{ app()->getLocale() === 'bn' ? 'en' : 'bn' }}" class="rounded-full border border-white/25 px-3 py-1.5 font-semibold transition hover:bg-white/10">{{ app()->getLocale() === 'bn' ? 'English' : 'বাংলা' }}</a>
            @endif
            <a href="{{ $lr('dealer.apply') }}" class="btn btn-primary hidden !px-5 !py-2 sm:inline-flex">{{ __('site.become_dealer') }}</a>
            <button type="button" class="rounded-control border border-white/30 p-2 md:hidden" x-on:click="nav = !nav" :aria-expanded="nav" aria-controls="mobile-nav">
                <span class="sr-only">{{ __('site.menu') }}</span>
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M4 6h16M4 12h16M4 18h16"/></svg>
            </button>
        </div>
    </div>

    <nav id="mobile-nav" class="border-t border-white/10 bg-navy-deep md:hidden" x-show="nav" x-cloak aria-label="Mobile">
        <div class="container-x py-4">
            <form action="{{ $lr('search') }}" method="GET" role="search" class="mb-3"><label for="mq" class="sr-only">{{ __('site.search') }}</label>
                <input id="mq" name="q" type="search" placeholder="{{ __('site.search_placeholder') }}" class="w-full rounded-full border border-white/15 bg-white/10 px-4 py-2.5 text-white placeholder:text-white/60"></form>
            <ul class="divide-y divide-white/10">
                @foreach (collect($navItems)->whereNull('parent_id') as $item)<li><a href="{{ $item['url'] }}" class="block py-3 font-semibold">{{ $item['label'] }}</a></li>@endforeach
                <li><a href="{{ $lr('dealer.apply') }}" class="block py-3 font-semibold text-amber">{{ __('site.become_dealer') }}</a></li>
            </ul>
        </div>
    </nav>
</header>

@if ($breadcrumbs)
    <nav aria-label="Breadcrumb" class="border-b border-steel/60 bg-surface text-sm">
        <ol class="container-x flex flex-wrap items-center gap-2 py-2.5">
            @foreach ($breadcrumbs as $i => [$label, $url])
                <li class="flex items-center gap-2">@if ($loop->last)<span aria-current="page" class="font-semibold text-navy">{{ $label }}</span>@else<a href="{{ $url }}" class="text-industrial hover:underline">{{ $label }}</a><span aria-hidden="true" class="text-steel">/</span>@endif</li>
            @endforeach
        </ol>
    </nav>
@endif

<x-ad place="header" />

<main id="main" class="flex-1">
    @if (session('status'))<p role="status" class="container-x mt-6 rounded-control border border-green-300 bg-green-50 p-4 text-sm font-semibold text-green-900">{{ session('status') }}</p>@endif
    {{ $slot }}
</main>

<x-ad place="footer" />

{{-- ===== FOOTER ===== --}}
<footer class="mt-20 bg-navy-deep text-white/80">
    <div class="container-x grid gap-10 py-14 sm:grid-cols-2 lg:grid-cols-[1.3fr_1fr_1fr_1.3fr]">
        <div>
            @if ($footerLogo = \App\Support\Settings::get('brand', 'logo_url'))
                <a href="{{ $lr('home') }}" class="inline-flex rounded-control bg-white px-4 py-2.5"><img src="{{ $footerLogo }}" alt="{{ $siteName }}" class="h-11 w-auto" loading="lazy"></a>
            @else
                <p class="text-2xl font-extrabold tracking-tight text-white">{{ $siteName }}</p>
            @endif
            @if ($t = \App\Support\Settings::get('brand', 'tagline'))<p class="mt-4 max-w-xs leading-relaxed">{{ $t }}</p>@endif
            <ul class="mt-5 flex flex-wrap gap-2">
                @foreach (['facebook' => 'Facebook', 'linkedin' => 'LinkedIn', 'youtube' => 'YouTube', 'instagram' => 'Instagram'] as $key => $label)
                    @if ($v = \App\Support\Settings::get('social', $key))<li><a href="{{ $v }}" rel="noopener" target="_blank" class="rounded-full border border-white/25 px-3 py-1 text-xs font-semibold transition hover:border-amber hover:text-amber">{{ $label }}</a></li>@endif
                @endforeach
            </ul>
        </div>
        <div>
            <h2 class="text-sm font-bold uppercase tracking-wider text-white">{{ __('site.footer_products') }}</h2>
            <ul class="mt-4 space-y-2 text-sm">@foreach ($footerCategories as $c)<li><a class="transition hover:text-amber" href="{{ $lr('catalog.index') }}?category={{ $c['slug'] }}">{{ $c['name'] }}</a></li>@endforeach</ul>
        </div>
        <div>
            <h2 class="text-sm font-bold uppercase tracking-wider text-white">{{ __('site.footer_company') }}</h2>
            <ul class="mt-4 space-y-2 text-sm">
                <li><a class="transition hover:text-amber" href="{{ $lr('home') }}">{{ __('site.home') }}</a></li>
                @foreach ($footerPages as $p)<li><a class="transition hover:text-amber" href="{{ $lr('page.show', ['page' => $p['slug']]) }}">{{ $p['title'] }}</a></li>@endforeach
                <li><a class="transition hover:text-amber" href="{{ $lr('resources.index') }}">{{ __('site.resources') }}</a></li>
                <li><a class="transition hover:text-amber" href="{{ $lr('dealers.index') }}">{{ __('site.dealers') }}</a></li>
                <li><a class="transition hover:text-amber" href="{{ $lr('blog.index') }}">{{ __('site.blog') }}</a></li>
                <li><a class="transition hover:text-amber" href="{{ $lr('contact.create') }}">{{ __('site.contact') }}</a></li>
            </ul>
        </div>
        <div>
            <h2 class="text-sm font-bold uppercase tracking-wider text-white">{{ __('site.footer_contact') }}</h2>
            <dl class="mt-4 space-y-4 text-sm">
                @if ($v = \App\Support\Settings::get('contact', 'address'))<div><dt class="text-xs font-bold uppercase tracking-wider text-amber">{{ __('site.address') }}</dt><dd class="mt-1 leading-relaxed">{{ $v }}</dd></div>@endif
                @if ($v = \App\Support\Settings::get('contact', 'phone'))<div><dt class="text-xs font-bold uppercase tracking-wider text-amber">{{ __('site.mobile') }}</dt><dd class="mt-1"><a class="transition hover:text-amber" href="tel:{{ preg_replace('/[^0-9+]/', '', $v) }}">{{ $v }}</a></dd></div>@endif
                @if ($v = \App\Support\Settings::get('contact', 'whatsapp'))<div><dt class="text-xs font-bold uppercase tracking-wider text-amber">WhatsApp</dt><dd class="mt-1"><a class="transition hover:text-amber" href="https://wa.me/{{ $v }}" target="_blank" rel="noopener">{{ $v }}</a></dd></div>@endif
                @if ($v = \App\Support\Settings::get('contact', 'email'))<div><dt class="text-xs font-bold uppercase tracking-wider text-amber">{{ __('site.email_short') }}</dt><dd class="mt-1 break-all"><a class="transition hover:text-amber" href="mailto:{{ $v }}">{{ $v }}</a></dd></div>@endif
                @if ($v = \App\Support\Settings::get('contact', 'business_hours'))<div><dt class="text-xs font-bold uppercase tracking-wider text-amber">{{ __('site.hours') }}</dt><dd class="mt-1">{{ $v }}</dd></div>@endif
            </dl>
        </div>
    </div>
    <div class="border-t border-white/10">
        <p class="container-x py-5 text-xs text-white/60">{{ \App\Support\Settings::get('footer', 'copyright', '© '.date('Y').' '.$siteName) }}</p>
    </div>
</footer>

{{-- Floating WhatsApp button: appears only when a WhatsApp number is saved in Settings. --}}
@if ($waNumber = \App\Support\Settings::get('contact', 'whatsapp'))
    <a href="https://wa.me/{{ $waNumber }}" target="_blank" rel="noopener" aria-label="WhatsApp"
       class="fixed bottom-24 right-5 z-40 flex h-14 w-14 items-center justify-center rounded-full bg-[#25D366] text-white shadow-lift transition hover:scale-110 md:bottom-6 motion-reduce:transition-none">
        <svg class="h-7 w-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 11.5a8 8 0 01-11.7 7.1L4 20l1.4-4.1A8 8 0 1120 11.5z"/><path d="M9 9.5c.3 2.2 2.3 4.2 4.5 4.5l1.3-1.2-1.6-.9-.8.7a3.6 3.6 0 01-1.7-1.7l.7-.8-.9-1.6z"/></svg>
    </a>
@endif

{{-- Comparison tray: shown only when the visitor has selected products. --}}
<div x-data x-show="$store.compare.items.length > 1" x-cloak class="fixed bottom-4 left-1/2 z-40 flex -translate-x-1/2 items-center gap-4 rounded-full bg-navy px-5 py-3 text-white shadow-lift">
    <span x-text="$store.compare.items.length + ' / 4'"></span>
    <a :href="$store.compare.url('{{ $lr('compare') }}')" class="rounded-full bg-amber px-4 py-1.5 font-semibold text-navy">{{ __('site.compare_selected') }}</a>
    <button type="button" class="underline" x-on:click="$store.compare.clear()">{{ __('site.clear') }}</button>
</div>

<div x-data="consent()" x-show="show" x-cloak role="region" aria-label="Cookies" class="fixed inset-x-0 bottom-0 z-50 border-t border-steel bg-white p-4 shadow-lift">
    <div class="container-x flex flex-col gap-3 text-sm sm:flex-row sm:items-center">
        <p class="flex-1">{{ __('site.cookies_text') }}</p>
        <button type="button" class="btn btn-outline !px-4 !py-2" x-on:click="choose('essential')">{{ __('site.essential_only') }}</button>
        <button type="button" class="btn btn-dark !px-4 !py-2" x-on:click="choose('all')">{{ __('site.accept_all') }}</button>
    </div>
</div>
</body>
</html>
