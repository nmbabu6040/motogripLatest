<x-layouts.public-shell :title="__('site.apply_dealer')" :noindex="true">
    <x-page-header :title="__('site.apply_dealer')" :eyebrow="__('site.dealers')" />
    <div class="container-x py-12">
        <div class="card mx-auto max-w-xl p-8">
            <p class="text-lg">{{ __('site.inquiry_sent') }}</p>
            <dl class="mt-6 rounded-control border border-amber bg-amber/10 p-5 text-sm">
                <dt class="font-bold uppercase tracking-wider text-ink/70">{{ __('site.reference') }}</dt>
                <dd class="mt-1 break-all font-mono">{{ $tracking['reference'] }}</dd>
                <dt class="mt-4 font-bold uppercase tracking-wider text-ink/70">{{ __('site.tracking_code') }}</dt>
                <dd class="mt-1 font-mono text-lg">{{ $tracking['token'] }}</dd>
            </dl>
            <p class="mt-3 text-sm">Save these now. The code is shown only once and is also emailed to you.</p>
            <a class="btn btn-dark mt-6" href="{{ $lr('dealer.status') }}">{{ __('site.track_application') }}</a>
        </div>
    </div>
</x-layouts.public-shell>
