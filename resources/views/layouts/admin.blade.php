@php
    $user = auth()->user();
    $icons = [
        'home' => 'M3 12l9-9 9 9M5 10v10h14V10',
        'box' => 'M21 8l-9-5-9 5 9 5 9-5zM3 8v8l9 5 9-5V8M12 13v8',
        'folder' => 'M3 7h6l2 2h10v10H3z',
        'file' => 'M7 3h7l5 5v13H7zM14 3v5h5',
        'mail' => 'M3 6h18v12H3zM3 7l9 7 9-7',
        'user' => 'M12 12a4 4 0 100-8 4 4 0 000 8zM4 21c0-4 4-6 8-6s8 2 8 6',
        'pin' => 'M12 21s7-6 7-11a7 7 0 10-14 0c0 5 7 11 7 11zM12 12a2 2 0 100-4 2 2 0 000 4z',
        'pencil' => 'M4 20h4L19 9l-4-4L4 16zM14 6l4 4',
        'chat' => 'M4 5h16v11H9l-5 4z',
        'page' => 'M6 3h12v18H6zM9 8h6M9 12h6M9 16h4',
        'layout' => 'M3 4h18v6H3zM3 14h8v6H3zM15 14h6v6h-6z',
        'search' => 'M11 4a7 7 0 100 14 7 7 0 000-14zM21 21l-5-5',
        'share' => 'M18 8a3 3 0 100-6 3 3 0 000 6zM6 15a3 3 0 100-6 3 3 0 000 6zM18 22a3 3 0 100-6 3 3 0 000 6zM8.6 13.5l6.8 4M15.4 6.5l-6.8 4',
        'shield' => 'M12 3l8 3v6c0 5-3.5 8-8 9-4.5-1-8-4-8-9V6z',
        'gear' => 'M12 8a4 4 0 100 8 4 4 0 000-8zM12 2v3M12 19v3M2 12h3M19 12h3M5 5l2 2M17 17l2 2M19 5l-2 2M7 17l-2 2',
        'db' => 'M4 6c0-2 16-2 16 0v12c0 2-16 2-16 0zM4 12c0 2 16 2 16 0',
        'clock' => 'M12 3a9 9 0 100 18 9 9 0 000-18zM12 7v5l3 2',
        'help' => 'M12 3a9 9 0 100 18 9 9 0 000-18zM9.5 9a2.5 2.5 0 115 0c0 1.5-2.5 2-2.5 3.5M12 17h.01',
        'lock' => 'M6 11h12v10H6zM8 11V7a4 4 0 118 0v4',
    ];
    // [label, route, permission|null, icon]
    $groups = [
        '' => [['Dashboard', 'admin.dashboard', 'dashboard.view', 'home']],
        'Catalog' => [
            ['Products', 'admin.products.index', 'products.view', 'box'],
            ['Categories', 'admin.categories.index', 'products.view', 'folder'],
            ['Documents (TDS/SDS)', 'admin.documents.index', 'documents.manage', 'file'],
        ],
        'Sales' => [
            ['Inquiries', 'admin.inquiries.index', 'inquiries.manage', 'mail'],
            ['Dealer applications', 'admin.dealer-applications.index', 'dealers.manage', 'user'],
            ['Dealers', 'admin.dealers.index', 'dealers.manage', 'pin'],
            ['Newsletter', 'admin.newsletter.index', 'newsletter.manage', 'mail'],
        ],
        'Content' => [
            ['Articles', 'admin.blog.index', 'blog.manage', 'pencil'],
            ['Comments', 'admin.comments.index', 'comments.moderate', 'chat'],
            ['Pages', 'admin.pages.index', 'pages.manage', 'page'],
            ['Homepage', 'admin.homepage.index', 'homepage.manage', 'layout'],
            ['FAQs', 'admin.faqs.index', 'homepage.manage', 'help'],
        ],
        'Growth' => [
            ['SEO', 'admin.seo.index', 'seo.manage', 'search'],
            ['Affiliates', 'admin.affiliates.index', 'affiliates.manage', 'share'],
            ['My affiliate area', 'affiliate.dashboard', 'affiliate-area', 'share'],
        ],
        'System' => [
            ['Users', 'admin.users.index', 'users.manage', 'user'],
            ['Roles', 'admin.roles.index', 'roles.manage', 'shield'],
            ['Settings', 'admin.settings.edit', 'settings.manage', 'gear'],
            ['Backups', 'admin.backups.index', 'backups.create', 'db'],
            ['Audit log', 'admin.audit.index', 'audit.view', 'clock'],
        ],
        'Account' => [['My security', 'admin.security', null, 'lock']],
    ];
    $allowed = fn ($perm) => $perm === 'affiliate-area'
        ? (config('motogrip.features.affiliate') && $user->hasRole('affiliate'))
        : (! $perm || $user->can($perm));
    $siteName = \App\Support\Settings::get('brand', 'site_name', config('app.name'));
    $logo = \App\Support\Settings::get('brand', 'logo_url');
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <title>{{ $title ?? 'Admin' }} | {{ $siteName }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js', 'resources/js/admin.js'])
</head>
<body class="min-h-screen bg-surface text-ink" x-data="{ menu: false }" x-on:keydown.escape.window="menu = false">
<a href="#content" class="sr-only focus:not-sr-only focus:fixed focus:top-2 focus:left-2 focus:z-50 focus:bg-navy focus:text-white focus:px-4 focus:py-2">Skip to content</a>

{{-- Mobile overlay --}}
<div x-show="menu" x-cloak x-on:click="menu = false" class="fixed inset-0 z-30 bg-black/50 md:hidden" aria-hidden="true"></div>

<div class="md:flex">
    <aside id="sidebar" aria-label="Admin navigation"
           class="fixed inset-y-0 left-0 z-40 flex w-64 shrink-0 -translate-x-full flex-col bg-navy text-white transition-transform duration-200 md:sticky md:top-0 md:h-screen md:translate-x-0 motion-reduce:transition-none"
           :class="menu ? '!translate-x-0' : ''">
        <div class="flex items-center gap-3 border-b border-white/10 px-5 py-4">
            @if ($logo)<img src="{{ $logo }}" alt="{{ $siteName }}" class="h-8 w-auto">@else
                <span class="flex h-9 w-9 items-center justify-center rounded-control bg-amber text-lg font-extrabold text-navy">{{ mb_strtoupper(mb_substr($siteName, 0, 1)) }}</span>
                <span class="text-lg font-extrabold leading-tight">{{ $siteName }}</span>
            @endif
        </div>

        <nav class="flex-1 overflow-y-auto px-3 py-4">
            @foreach ($groups as $heading => $items)
                @php $visible = collect($items)->filter(fn ($i) => $allowed($i[2])); @endphp
                @if ($visible->isNotEmpty())
                    @if ($heading)<p class="mb-1 mt-5 px-3 text-xs font-semibold uppercase tracking-wider text-white/50">{{ $heading }}</p>@endif
                    <ul class="space-y-0.5">
                        @foreach ($visible as [$label, $route, $perm, $icon])
                            @php $active = request()->routeIs($route) || (str_ends_with($route, '.index') && request()->routeIs(substr($route, 0, -6).'.*')); @endphp
                            <li>
                                <a href="{{ route($route) }}" @if ($active) aria-current="page" @endif
                                   class="group flex items-center gap-3 rounded-control border-l-4 px-3 py-2 text-sm transition-colors {{ $active ? 'border-amber bg-white/10 font-semibold text-white' : 'border-transparent text-white/80 hover:bg-white/5 hover:text-white' }}">
                                    <svg class="h-5 w-5 shrink-0 {{ $active ? 'text-amber' : 'text-white/60 group-hover:text-white' }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="{{ $icons[$icon] }}"/></svg>
                                    <span>{{ $label }}</span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                @endif
            @endforeach
        </nav>

        <div class="border-t border-white/10 p-4">
            <div class="flex items-center gap-3">
                <span class="flex h-9 w-9 items-center justify-center rounded-full bg-industrial font-bold">{{ mb_strtoupper(mb_substr($user->name, 0, 1)) }}</span>
                <div class="min-w-0"><p class="truncate text-sm font-semibold">{{ $user->name }}</p><p class="truncate text-xs text-white/60">{{ $user->getRoleNames()->first() ?? 'user' }}</p></div>
            </div>
            <form method="POST" action="{{ route('logout') }}" class="mt-3">@csrf
                <button class="w-full rounded-control border border-white/20 px-3 py-2 text-sm hover:bg-white/10">Sign out</button>
            </form>
        </div>
    </aside>

    <div class="min-w-0 flex-1">
        <header class="sticky top-0 z-20 flex items-center justify-between gap-3 border-b border-steel bg-white px-4 py-3">
            <div class="flex items-center gap-3">
                <button type="button" class="rounded-control border border-steel p-2 md:hidden" x-on:click="menu = !menu" :aria-expanded="menu" aria-controls="sidebar">
                    <span class="sr-only">Menu</span>
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M4 6h16M4 12h16M4 18h16"/></svg>
                </button>
                <h1 class="text-xl font-extrabold text-navy">{{ $title ?? 'Admin' }}</h1>
            </div>
            <a href="{{ url('/') }}" target="_blank" rel="noopener" class="rounded-control border border-steel px-3 py-1.5 text-sm font-semibold text-industrial hover:bg-surface">View website ↗</a>
        </header>

        <main id="content" class="p-4 md:p-6">
            @if (session('status'))<p role="status" class="mb-4 rounded-control border border-green-300 bg-green-50 p-3 text-sm font-semibold text-green-900">{{ session('status') }}</p>@endif
            @if ($errors->any())
                <div role="alert" class="mb-4 rounded-control border border-red-300 bg-red-50 p-3 text-sm text-red-900">
                    <p class="font-semibold">Please fix the following:</p>
                    <ul class="list-disc pl-5">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                </div>
            @endif
            {{ $slot }}
        </main>
    </div>
</div>
</body>
</html>
