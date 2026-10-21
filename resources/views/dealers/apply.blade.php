<x-layouts.public-shell :title="__('site.apply_dealer')" :breadcrumbs="[[__('site.home'), $lr('home')], [__('site.apply_dealer'), $lr('dealer.apply')]]">
    <x-page-header :title="__('site.apply_dealer')" :eyebrow="__('site.dealers')" :subtitle="__('site.dealer_cta_text')" />
    <div class="container-x py-12">
        <section class="card mx-auto max-w-3xl p-6 sm:p-8">
            @if ($errors->any())
                <div role="alert" class="mb-5 rounded-control border border-red-300 bg-red-50 p-3 text-sm text-red-900">
                    <ul class="list-disc pl-5">
                        @foreach ($errors->all() as $e)
                            <li>{{ $e }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
            <form method="POST" action="{{ $lr('dealer.apply.store') }}" class="grid gap-x-5 sm:grid-cols-2">
                @csrf
                <x-field name="business_name" :label="__('site.business_name')" autocomplete="organization" required />
                <x-field name="contact_name" :label="__('site.name')" autocomplete="name" required />
                <x-field name="email" :label="__('site.email')" type="email" autocomplete="email" required />
                <x-field name="phone" :label="__('site.phone')" type="tel" autocomplete="tel" required />
                <x-field name="district" :label="__('site.district')" required />
                <x-field name="city" :label="__('site.city')" />
                <div class="sm:col-span-2"><x-field name="address" :label="__('site.address')" autocomplete="street-address" />
                </div>
                <div class="mb-4"><label class="mb-1 block text-sm font-semibold"
                        for="business_type">{{ __('site.business_type') }}</label>
                    <select id="business_type" name="business_type" class="field">
                        @foreach ($types as $k => $v)
                            <option value="{{ $k }}" @selected(old('business_type') === $k)>{{ $v }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <x-field name="years_in_business" :label="__('site.years')" type="number" min="0" />
                <div class="mb-4 sm:col-span-2"><label class="mb-1 block text-sm font-semibold"
                        for="message">{{ __('site.message') }}</label>
                    <textarea id="message" name="message" rows="5" class="field">{{ old('message') }}</textarea>
                </div>
                <div class="hidden" aria-hidden="true"><label>Website<input type="text" name="website" tabindex="-1"
                            autocomplete="off"></label></div>
                <label class="mb-5 flex items-start gap-2 text-sm sm:col-span-2"><input type="checkbox" name="consent"
                        value="1" required class="mt-1 accent-navy"> {{ __('site.consent') }}</label>
                <div class="flex flex-wrap items-center gap-5 sm:col-span-2"><button
                        class="btn btn-primary">{{ __('site.submit') }} <span aria-hidden="true">→</span></button><a
                        class="font-semibold text-industrial underline"
                        href="{{ $lr('dealer.status') }}">{{ __('site.track_application') }}</a></div>
            </form>
        </section>
    </div>
</x-layouts.public-shell>
