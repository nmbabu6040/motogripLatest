@props(['title', 'eyebrow' => null, 'subtitle' => null])
{{-- Dark banner used at the top of inner pages. --}}
<section class="relative isolate overflow-hidden bg-gradient-to-br from-navy-deep via-navy to-industrial text-white">
    <div class="bg-grid absolute inset-0 -z-10 opacity-60" aria-hidden="true"></div>
    <div class="absolute -right-24 -top-24 -z-10 h-72 w-72 rounded-full bg-amber/15 blur-3xl" aria-hidden="true"></div>
    <div class="container-x py-12 sm:py-14">
        @if ($eyebrow)<p class="text-xs font-bold uppercase tracking-[0.2em] text-amber">{{ $eyebrow }}</p>@endif
        <h1 class="mt-2 text-4xl font-extrabold tracking-tight sm:text-5xl">{{ $title }}</h1>
        @if ($subtitle)<p class="mt-3 max-w-2xl text-lg text-white/75">{{ $subtitle }}</p>@endif
        {{ $slot }}
    </div>
</section>
