@php
    $title = \App\Support\Lang::pick($content['title'] ?? '');
    $body = \App\Support\Lang::pick($content['body'] ?? '');
    $items = collect($content['items'] ?? [])
        ->map(
            fn($i) => [
                'title' => \App\Support\Lang::pick($i['title'] ?? ''),
                'text' => \App\Support\Lang::pick($i['text'] ?? ''),
            ],
        )
        ->filter(fn($i) => $i['title'] || $i['text']);

    // Two call-to-action bands have sensible default wording until an admin writes their own.
    $fallback = [
        'dealer_recruitment' => ['dealer_cta_title', 'dealer_cta_text'],
        'inquiry_cta' => ['inquiry_cta_title', 'inquiry_cta_text'],
    ];
    if (!$title && !$body && $items->isEmpty() && isset($fallback[$key])) {
        $title = __('site.' . $fallback[$key][0]);
        $body = __('site.' . $fallback[$key][1]);
    }
    $dark = in_array($key, ['why_choose', 'dealer_recruitment', 'inquiry_cta', 'statistics'], true);
    $center = in_array($key, ['dealer_recruitment', 'inquiry_cta'], true);
    $cols = [1 => 'lg:grid-cols-1', 2 => 'lg:grid-cols-2', 3 => 'lg:grid-cols-3', 4 => 'lg:grid-cols-4'][
        min(max($items->count(), 1), 4)
    ];
@endphp
@if ($title || $body || $items->isNotEmpty())
    <section
        class="{{ $dark ? 'relative isolate overflow-hidden bg-gradient-to-br from-navy-deep via-navy to-industrial text-white' : 'bg-white' }} py-20"
        data-reveal>
        @if ($dark)
            <div class="bg-grid absolute inset-0 -z-10 opacity-60" aria-hidden="true"></div>
        @endif
        <div class="container-x {{ $center ? 'text-center' : '' }}">
            @if ($title)
                <h2 class="text-3xl font-extrabold tracking-tight sm:text-4xl {{ $dark ? '' : 'text-navy' }}">
                    {{ $title }}</h2>
                <span class="bar {{ $center ? 'mx-auto' : '' }}" aria-hidden="true"></span>
            @endif
            @if ($body)
                <p
                    class="mt-5 max-w-3xl whitespace-pre-line text-lg leading-relaxed {{ $dark ? 'text-white/80' : '' }} {{ $center ? 'mx-auto' : '' }}">
                    {{ $body }}</p>
            @endif
            @if ($items->isNotEmpty())
                <dl class="mt-10 grid gap-6 sm:grid-cols-2 {{ $cols }}">
                    @foreach ($items as $i)
                        <div
                            class="rounded-panel border-t-4 border-amber p-6 {{ $dark ? 'bg-white/10 backdrop-blur' : 'card' }}">
                            @if ($key === 'statistics')
                                <dd class="text-4xl font-extrabold text-amber">{{ $i['text'] }}</dd>
                                <dt class="mt-1 text-sm">{{ $i['title'] }}</dt>
                            @else<dt class="text-lg font-bold">{{ $i['title'] }}</dt>
                                <dd class="mt-2 text-sm leading-relaxed {{ $dark ? 'text-white/80' : '' }}">
                                    {{ $i['text'] }}</dd>
                            @endif
                        </div>
                    @endforeach
                </dl>
            @endif
            @if ($key === 'dealer_recruitment')
                <a href="{{ $lr('dealer.apply') }}" class="btn btn-primary mt-9">{{ __('site.become_dealer') }} <span
                        aria-hidden="true">→</span></a>
            @endif
            @if ($key === 'inquiry_cta')
                <a href="{{ $lr('contact.create') }}" class="btn btn-primary mt-9">{{ __('site.contact_sales') }}
                    <span aria-hidden="true">→</span></a>
            @endif
        </div>
    </section>
@endif
