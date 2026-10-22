<x-layouts.public-shell :title="__('site.track_application')" :noindex="true">
    <x-page-header :title="__('site.track_application')" :eyebrow="__('site.dealers')" />
    <div class="container-x py-12">
        <div class="card mx-auto max-w-xl p-8">
            @isset($application)
                <p>{{ __('site.status') }}</p>
                <p class="mt-1 text-3xl font-extrabold text-navy">{{ ucfirst(str_replace('_', ' ', $application->status)) }}</p>
                <p class="mt-3 text-sm text-ink/70">{{ $application->business_name }} · {{ $application->created_at->format('Y-m-d') }}</p>
            @else
                @if ($errors->any())<p role="alert" class="mb-4 rounded-control border border-red-300 bg-red-50 p-3 text-sm text-red-900">{{ $errors->first() }}</p>@endif
                <form method="POST" action="{{ $lr('dealer.status.check') }}">
                    @csrf
                    <x-field name="reference" :label="__('site.reference')" required />
                    <x-field name="token" :label="__('site.tracking_code')" autocomplete="off" required />
                    <button class="btn btn-dark">{{ __('site.submit') }}</button>
                </form>
            @endisset
        </div>
    </div>
</x-layouts.public-shell>
