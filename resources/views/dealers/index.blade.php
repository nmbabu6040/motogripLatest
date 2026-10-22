<x-layouts.public-shell :title="__('site.find_dealer')" :breadcrumbs="[[__('site.home'), $lr('home')], [__('site.dealers'), $lr('dealers.index')]]">
    <x-page-header :title="__('site.find_dealer')" :eyebrow="__('site.dealers')" :subtitle="__('site.dealers_intro')">
        <form method="GET" role="search" class="mt-6 flex max-w-xl flex-col gap-3 sm:flex-row">
            <label for="q" class="sr-only">{{ __('site.search_dealers') }}</label>
            <input id="q" name="q" value="{{ $term }}" placeholder="{{ __('site.search_dealers') }}" class="flex-1 rounded-control border-0 bg-white px-4 py-3 text-ink placeholder:text-ink/60">
            <button class="btn btn-primary">{{ __('site.search') }}</button>
        </form>
    </x-page-header>

    <div class="container-x py-12">
        @if ($dealers->isEmpty())
            <div class="card p-10 text-center"><p class="text-lg font-bold text-navy">{{ __('site.no_results') }}</p><a href="{{ $lr('dealer.apply') }}" class="btn btn-dark mt-5">{{ __('site.become_dealer') }}</a></div>
        @else
            <ul class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($dealers as $d)
                    <li class="card flex flex-col p-6">
                        <p class="eyebrow">{{ $d->district }}</p>
                        <h2 class="mt-1 text-xl font-extrabold text-navy">{{ $d->name }}</h2>
                        <p class="mt-2 text-sm">{{ collect([$d->address, $d->city, $d->district])->filter()->join(', ') }}</p>
                        <div class="mt-3 space-y-1 text-sm">
                            @if ($d->show_phone && $d->phone)<p><a class="font-semibold text-industrial hover:underline" href="tel:{{ preg_replace('/[^0-9+]/', '', $d->phone) }}">{{ $d->phone }}</a></p>@endif
                            @if ($d->show_email && $d->email)<p><a class="font-semibold text-industrial hover:underline" href="mailto:{{ $d->email }}">{{ $d->email }}</a></p>@endif
                            @if ($d->business_hours)<p class="text-ink/70">{{ is_array($d->business_hours) ? ($d->business_hours['text'] ?? '') : $d->business_hours }}</p>@endif
                        </div>
                        @if ($d->mapUrl())<a class="mt-auto pt-4 text-sm font-bold text-industrial hover:underline" href="{{ $d->mapUrl() }}" target="_blank" rel="noopener">{{ __('site.view_map') }} →</a>@endif
                    </li>
                @endforeach
            </ul>
            <div class="mt-8">{{ $dealers->links() }}</div>
        @endif

        <div class="mt-14 rounded-panel bg-gradient-to-r from-navy to-industrial p-8 text-center text-white shadow-card">
            <h2 class="text-2xl font-extrabold">{{ __('site.dealer_cta_title') }}</h2>
            <p class="mx-auto mt-2 max-w-xl text-white/80">{{ __('site.dealer_cta_text') }}</p>
            <a href="{{ $lr('dealer.apply') }}" class="btn btn-primary mt-6">{{ __('site.become_dealer') }}</a>
        </div>
    </div>
</x-layouts.public-shell>
