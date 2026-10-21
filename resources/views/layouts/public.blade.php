<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <x-seo :title="$title ?? null" :description="$description ?? null" :canonical="$canonical ?? null" :image="$image ?? null" :noindex="$noindex ?? false" />
    @if ($favicon = \App\Support\Settings::get('brand', 'favicon_url'))
        <link rel="icon" href="{{ $favicon }}">
    @endif
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    {{ $head ?? '' }}
</head>

<body class="min-h-screen flex flex-col">
    <a href="#main"
        class="sr-only focus:not-sr-only focus:fixed focus:top-2 focus:left-2 focus:z-50 focus:bg-navy focus:text-white focus:px-4 focus:py-2 focus:rounded-control">
        Skip to main content
    </a>

    <header class="bg-navy text-white">
        <div class="mx-auto max-w-7xl px-4 py-4 flex items-center justify-between">
            <a href="{{ url('/') }}" class="text-xl font-extrabold tracking-tight">
                {{ \App\Support\Settings::get('brand', 'site_name', config('app.name')) }}
            </a>
            {{-- Navigation is rendered from the menus table in Phase 4. --}}
        </div>
    </header>

    <main id="main" class="flex-1">
        {{ $slot }}
    </main>

    <footer class="bg-navy text-white/80">
        <div class="mx-auto max-w-7xl px-4 py-10 text-sm">
            {{ \App\Support\Settings::get('footer', 'copyright', '© ' . date('Y') . ' ' . config('app.name')) }}
        </div>
    </footer>
</body>

</html>
