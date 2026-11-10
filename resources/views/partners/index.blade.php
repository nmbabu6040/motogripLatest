<x-layouts.public-shell :title="__('site.recommended')" :description="__('site.recommended_intro')" :breadcrumbs="[[__('site.home'), $lr('home')], [__('site.recommended'), $lr('partners.index')]]">
    <x-page-header :title="__('site.recommended')" :eyebrow="__('site.products')" :subtitle="__('site.recommended_intro')" />
    <div class="container-x py-12">
        <div class="mb-8 rounded-panel border border-amber/60 bg-amber/10 p-5 text-sm leading-relaxed" role="note">
            @foreach ($notices as $n)<p class="{{ $loop->first ? '' : 'mt-2' }} font-semibold">{{ $n }}</p>@endforeach
        </div>
        <ul class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($links as $l)
                <li class="card flex flex-col overflow-hidden">
                    <div class="flex h-44 items-center justify-center bg-gradient-to-b from-white to-surface">
                        @if ($l->image)<img src="{{ $l->image }}" alt="{{ $l->title }}" loading="lazy" class="h-full w-full object-contain p-4">@else<span class="text-sm text-ink/50">{{ \App\Models\PartnerLink::STORES[$l->store] ?? '' }}</span>@endif
                    </div>
                    <div class="flex flex-1 flex-col p-6">
                        <p class="eyebrow">{{ \App\Models\PartnerLink::STORES[$l->store] ?? '' }}</p>
                        <h2 class="mt-1 text-lg font-extrabold text-navy">{{ $l->title }}</h2>
                        @if ($l->description)<p class="mt-2 text-sm leading-relaxed">{{ $l->description }}</p>@endif
                        <a href="{{ $l->url }}" target="_blank" rel="sponsored nofollow noopener" data-partner-click="{{ route('partners.click', $l) }}" class="btn btn-primary mt-auto self-start">{{ __('site.view_on', ['store' => \App\Models\PartnerLink::STORES[$l->store] ?? '']) }} <span aria-hidden="true">↗</span></a>
                    </div>
                </li>
            @endforeach
        </ul>
    </div>
</x-layouts.public-shell>
