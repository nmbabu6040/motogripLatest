@if ($categories->isNotEmpty())
    <section class="bg-surface py-20" data-reveal>
        <div class="container-x">
            <p class="eyebrow">{{ __('site.products') }}</p>
            <h2 class="section-title">{{ \App\Support\Lang::pick($content['title'] ?? '') ?: __('site.categories') }}
            </h2>
            <span class="bar" aria-hidden="true"></span>
            <ul class="mt-10 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ($categories as $c)
                    <li>
                        <a href="{{ $lr('catalog.index') }}?category={{ $c->slug }}"
                            class="group relative flex h-full min-h-[8.5rem] flex-col justify-between overflow-hidden rounded-panel border border-steel/70 bg-white p-6 shadow-card transition duration-300 hover:-translate-y-1 hover:border-navy hover:bg-navy motion-reduce:transition-none motion-reduce:hover:translate-y-0">
                            <span
                                class="text-sm font-bold text-amber">{{ str_pad($loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>
                            <span class="mt-6 flex items-end justify-between gap-3">
                                <span
                                    class="text-lg font-extrabold leading-snug text-navy transition group-hover:text-white">{{ $c->name }}</span>
                                <span
                                    class="text-xl text-amber transition group-hover:translate-x-1 motion-reduce:transition-none"
                                    aria-hidden="true">→</span>
                            </span>
                        </a>
                    </li>
                @endforeach
            </ul>
        </div>
    </section>
@endif
