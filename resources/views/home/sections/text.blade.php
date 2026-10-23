@php
    $title = \App\Support\Lang::pick($content['title'] ?? '');
    $body = \App\Support\Lang::pick($content['body'] ?? '');
    $image = $content['image'] ?? null;
    $items = collect($content['items'] ?? [])->map(fn ($i) => ['title' => \App\Support\Lang::pick($i['title'] ?? ''), 'text' => \App\Support\Lang::pick($i['text'] ?? '')])->filter(fn ($i) => $i['title'] || $i['text'])->values();

    // Two call-to-action bands have sensible default wording until an admin writes their own.
    $fallback = ['dealer_recruitment' => ['dealer_cta_title', 'dealer_cta_text'], 'inquiry_cta' => ['inquiry_cta_title', 'inquiry_cta_text']];
    if (! $title && ! $body && $items->isEmpty() && isset($fallback[$key])) {
        $title = __('site.'.$fallback[$key][0]);
        $body = __('site.'.$fallback[$key][1]);
    }

    $isCta = in_array($key, ['dealer_recruitment', 'inquiry_cta'], true);
    $isStats = $key === 'statistics';
    $dark = in_array($key, ['why_choose', 'dealer_recruitment', 'statistics'], true);
    $tint = $key === 'inquiry_cta';
    $surface = in_array($key, ['automotive_solutions', 'benefits'], true);
    $checks = $items->isNotEmpty() && $items->every(fn ($i) => ! $i['text']);   // points with titles only become a checklist
    $layout = $content['layout'] ?? 'image_right';
    $banner = $image && $layout === 'banner' && ! $isCta && ! $isStats;                    // wide photo above the text
    $split = ! $isCta && ! $isStats && ! $banner && ($image || $checks);                     // text and photo (or checklist) side by side
    $imgFirst = $layout === 'image_left';
    $bg = $dark ? 'relative isolate overflow-hidden bg-gradient-to-br from-navy-deep via-navy to-industrial text-white' : ($tint ? 'bg-amber/15' : ($surface ? 'bg-surface' : 'bg-white'));
    $cols = [1 => 'lg:grid-cols-1', 2 => 'lg:grid-cols-2', 3 => 'lg:grid-cols-3', 4 => 'lg:grid-cols-4'][min(max($items->count(), 1), 4)];
@endphp
@if ($title || $body || $items->isNotEmpty() || $image)
<section class="{{ $bg }} py-20" data-reveal data-layout="{{ $banner ? 'banner' : ($split ? $layout : 'text') }}">
    @if ($dark)<div class="bg-grid absolute inset-0 -z-10 opacity-60" aria-hidden="true"></div>@endif
    <div class="container-x">

        @if ($split)
            <div class="grid items-center gap-12 lg:grid-cols-2">
                <div class="{{ $imgFirst ? 'lg:order-2' : '' }}">
                    @if ($title)<h2 class="text-3xl font-extrabold tracking-tight sm:text-4xl {{ $dark ? '' : 'text-navy' }}">{{ $title }}</h2><span class="bar" aria-hidden="true"></span>@endif
                    @if ($body)<p class="mt-5 whitespace-pre-line text-lg leading-relaxed {{ $dark ? 'text-white/80' : '' }}">{{ $body }}</p>@endif
                    @if ($image && $checks)
                        <ul class="mt-6 space-y-3">@foreach ($items as $i)
                            <li class="flex gap-3"><svg class="mt-1 h-5 w-5 shrink-0 text-amber" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 13l4 4L19 7"/></svg><span>{{ $i['title'] }}</span></li>
                        @endforeach</ul>
                    @endif
                </div>
                <div class="{{ $imgFirst ? 'lg:order-1' : '' }}">
                    @if ($image)
                        <img src="{{ $image }}" alt="{{ $title }}" loading="lazy" class="w-full rounded-panel object-cover shadow-lift ring-1 ring-steel/40">
                    @else
                        <ul class="space-y-3 rounded-panel p-6 sm:p-8 {{ $dark ? 'bg-white/10 backdrop-blur' : 'card' }}">@foreach ($items as $i)
                            <li class="flex gap-3 {{ $loop->last ? '' : 'border-b pb-3 '.($dark ? 'border-white/15' : 'border-steel/60') }}"><svg class="mt-1 h-5 w-5 shrink-0 text-amber" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 13l4 4L19 7"/></svg><span class="font-semibold {{ $dark ? '' : 'text-navy' }}">{{ $i['title'] }}</span></li>
                        @endforeach</ul>
                    @endif
                </div>
            </div>

        @else
            {{-- Centered heading for call-to-action bands, numbers, and text-only sections --}}
            @if ($banner)<img src="{{ $image }}" alt="{{ $title }}" loading="lazy" class="mb-10 max-h-[28rem] w-full rounded-panel object-cover shadow-lift ring-1 ring-steel/40">@endif
            @php $center = $isCta || $isStats || $items->isEmpty(); @endphp
            <div class="{{ $center ? 'mx-auto max-w-3xl text-center' : 'max-w-3xl' }}">
                @if ($title)<h2 class="text-3xl font-extrabold tracking-tight sm:text-4xl {{ $dark ? '' : 'text-navy' }}">{{ $title }}</h2><span class="bar {{ $center ? 'mx-auto' : '' }}" aria-hidden="true"></span>@endif
                @if ($body)<p class="mt-5 whitespace-pre-line text-lg leading-relaxed {{ $dark ? 'text-white/80' : '' }}">{{ $body }}</p>@endif
                @if ($key === 'dealer_recruitment')<a href="{{ $lr('dealer.apply') }}" class="btn btn-primary mt-9">{{ __('site.become_dealer') }} <span aria-hidden="true">→</span></a>@endif
                @if ($key === 'inquiry_cta')<a href="{{ $lr('contact.create') }}" class="btn btn-dark mt-9">{{ __('site.contact_sales') }} <span aria-hidden="true">→</span></a>@endif
            </div>
            @if ($items->isNotEmpty())
                <dl class="mt-10 grid gap-6 sm:grid-cols-2 {{ $cols }}">
                    @foreach ($items as $i)
                        <div class="rounded-panel border-t-4 border-amber p-6 {{ $dark ? 'bg-white/10 backdrop-blur' : 'card' }}">
                            @if ($isStats)<dd class="text-4xl font-extrabold text-amber">{{ $i['text'] }}</dd><dt class="mt-1 text-sm">{{ $i['title'] }}</dt>
                            @else<dt class="text-lg font-bold">{{ $i['title'] }}</dt><dd class="mt-2 text-sm leading-relaxed {{ $dark ? 'text-white/80' : '' }}">{{ $i['text'] }}</dd>@endif
                        </div>
                    @endforeach
                </dl>
            @endif
        @endif

    </div>
</section>
@endif
