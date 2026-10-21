@php
    $phone = \App\Support\Settings::get('contact', 'phone');
    $wa = \App\Support\Settings::get('contact', 'whatsapp');
    $mail = \App\Support\Settings::get('contact', 'email');
    $addr = \App\Support\Settings::get('contact', 'address');
    $hours = \App\Support\Settings::get('contact', 'business_hours');
@endphp
<x-layouts.public-shell :title="__('site.contact')" :breadcrumbs="[[__('site.home'), $lr('home')], [__('site.contact'), $lr('contact.create')]]">
    <x-page-header :title="__('site.contact_sales')" :eyebrow="__('site.contact')" :subtitle="__('site.contact_intro')" />

    <div class="container-x grid gap-8 py-12 lg:grid-cols-[1fr_1.7fr]">
        <aside class="self-start rounded-panel bg-gradient-to-br from-navy to-industrial p-8 text-white shadow-card">
            <h2 class="text-xl font-extrabold">{{ __('site.get_in_touch') }}</h2>
            <span class="bar" aria-hidden="true"></span>
            <ul class="mt-6 space-y-5 text-white/90">
                @if ($addr)
                    <li>
                        <p class="text-xs font-bold uppercase tracking-wider text-amber">{{ __('site.address') }}</p>
                        <p class="mt-1">{{ $addr }}</p>
                    </li>
                @endif
                @if ($phone)
                    <li>
                        <p class="text-xs font-bold uppercase tracking-wider text-amber">{{ __('site.phone') }}</p><a
                            class="mt-1 block hover:underline"
                            href="tel:{{ preg_replace('/[^0-9+]/', '', $phone) }}">{{ $phone }}</a>
                    </li>
                @endif
                @if ($wa)
                    <li>
                        <p class="text-xs font-bold uppercase tracking-wider text-amber">WhatsApp</p><a
                            class="mt-1 block hover:underline" href="https://wa.me/{{ $wa }}" target="_blank"
                            rel="noopener">{{ $wa }}</a>
                    </li>
                @endif
                @if ($mail)
                    <li>
                        <p class="text-xs font-bold uppercase tracking-wider text-amber">{{ __('site.email') }}</p><a
                            class="mt-1 block break-all hover:underline"
                            href="mailto:{{ $mail }}">{{ $mail }}</a>
                    </li>
                @endif
                @if ($hours)
                    <li>
                        <p class="text-xs font-bold uppercase tracking-wider text-amber">{{ __('site.hours') }}</p>
                        <p class="mt-1">{{ $hours }}</p>
                    </li>
                @endif
            </ul>
        </aside>

        <section class="card p-6 sm:p-8">
            @if ($errors->any())
                <div role="alert"
                    class="mb-5 rounded-control border border-red-300 bg-red-50 p-3 text-sm text-red-900">
                    <ul class="list-disc pl-5">
                        @foreach ($errors->all() as $e)
                            <li>{{ $e }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
            <form method="POST" action="{{ $lr('contact.store') }}" class="grid gap-x-5 sm:grid-cols-2">
                @csrf
                <div class="mb-4 sm:col-span-2">
                    <label class="mb-1 block text-sm font-semibold"
                        for="type">{{ __('site.inquiry_type') }}</label>
                    <select id="type" name="type" class="field">
                        @foreach ($types as $k => $v)
                            <option value="{{ $k }}" @selected(old('type', $product ? 'product' : 'contact') === $k)>{{ $v }}
                            </option>
                        @endforeach
                    </select>
                </div>
                @if ($product)
                    <input type="hidden" name="product" value="{{ $product->slug }}">
                    <p class="mb-4 rounded-control bg-surface p-3 text-sm sm:col-span-2">{{ __('site.products') }}:
                        <strong>{{ $product->name }}</strong> ({{ $product->sku }})</p>
                @endif
                <x-field name="name" :label="__('site.name')" autocomplete="name" required />
                <x-field name="email" :label="__('site.email')" type="email" autocomplete="email" required />
                <x-field name="phone" :label="__('site.phone')" type="tel" autocomplete="tel" />
                <x-field name="company" :label="__('site.company')" autocomplete="organization" />
                <div class="mb-4 sm:col-span-2"><label class="mb-1 block text-sm font-semibold"
                        for="message">{{ __('site.message') }}</label>
                    <textarea id="message" name="message" rows="6" required class="field">{{ old('message') }}</textarea>
                </div>
                <div class="hidden" aria-hidden="true"><label>Website<input type="text" name="website" tabindex="-1"
                            autocomplete="off"></label></div>
                <label class="mb-5 flex items-start gap-2 text-sm sm:col-span-2"><input type="checkbox" name="consent"
                        value="1" required class="mt-1 accent-navy"> {{ __('site.consent') }}</label>
                <div class="sm:col-span-2"><button class="btn btn-primary">{{ __('site.submit') }} <span
                            aria-hidden="true">→</span></button></div>
            </form>
        </section>
    </div>
</x-layouts.public-shell>
