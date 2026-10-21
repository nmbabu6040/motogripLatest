@if ($featured->isNotEmpty())
    <section class="py-20" data-reveal>
        <div class="container-x">
            <div class="flex flex-wrap items-end justify-between gap-4">
                <div>
                    <p class="eyebrow">{{ __('site.featured') }}</p>
                    <h2 class="section-title">
                        {{ \App\Support\Lang::pick($content['title'] ?? '') ?: __('site.products') }}</h2>
                    <span class="bar" aria-hidden="true"></span>
                </div>
                <a class="font-semibold text-industrial underline underline-offset-4 hover:text-navy"
                    href="{{ $lr('catalog.index') }}">{{ __('site.all_products') }} →</a>
            </div>

            @if ($featured->count() === 1)
                {{-- Only one product: show it as a wide showcase card --}}
                @php
                    $p = $featured->first();
                    $img = $p->getFirstMedia('images');
                @endphp
                <article class="card mt-10 grid overflow-hidden md:grid-cols-2">
                    <a href="{{ $lr('catalog.show', ['product' => $p]) }}"
                        class="flex items-center justify-center bg-gradient-to-b from-white to-surface p-8 md:p-12">
                        @if ($img)
                            <img src="{{ $img->hasGeneratedConversion('large') ? $img->getUrl('large') : $img->getUrl() }}"
                                alt="{{ $img->getCustomProperty('alt') ?: $p->name }}" loading="lazy" width="600"
                                height="600" class="max-h-[28rem] w-auto object-contain">
                        @else<span class="text-ink/60">{{ $p->sku }}</span>
                        @endif
                    </a>
                    <div class="flex flex-col justify-center p-8 md:p-12">
                        <p class="eyebrow">{{ $p->category?->name }}</p>
                        <h3 class="mt-2 text-3xl font-extrabold tracking-tight text-navy"><a
                                href="{{ $lr('catalog.show', ['product' => $p]) }}"
                                class="hover:underline">{{ $p->name }}</a></h3>
                        <p class="mt-2 text-sm text-ink/70">{{ __('site.sku') }}: {{ $p->sku }}@if ($p->viscosity_grade)
                                · {{ $p->viscosity_grade }}
                            @endif
                        </p>
                        @if ($p->short_description)
                            <p class="mt-4 leading-relaxed">{{ $p->short_description }}</p>
                        @endif
                        @if ($p->packaging->where('is_active', true)->isNotEmpty())
                            <ul class="mt-5 flex flex-wrap gap-2">
                                @foreach ($p->packaging->where('is_active', true) as $pack)
                                    <li class="chip">{{ $pack->label }}</li>
                                @endforeach
                            </ul>
                        @endif
                        <div class="mt-8 flex flex-wrap gap-3">
                            <a href="{{ $lr('catalog.show', ['product' => $p]) }}"
                                class="btn btn-dark">{{ __('site.view_details') }}</a>
                            <a href="{{ $lr('contact.create') }}?product={{ $p->slug }}"
                                class="btn btn-outline">{{ __('site.send_inquiry') }}</a>
                        </div>
                    </div>
                </article>
            @else
                <div
                    class="mt-10 grid gap-6 {{ $featured->count() === 2 ? 'sm:grid-cols-2' : ($featured->count() === 3 ? 'sm:grid-cols-2 lg:grid-cols-3' : 'sm:grid-cols-2 lg:grid-cols-4') }}">
                    @foreach ($featured as $product)
                        @include('catalog._card', ['product' => $product])
                    @endforeach
                </div>
            @endif
        </div>
    </section>
@endif
