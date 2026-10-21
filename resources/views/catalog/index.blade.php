@php
    $crumbs = [[__('site.home'), $lr('home')], [__('site.products'), $lr('catalog.index')]];
    $select = function ($name, $label, $options, $current) {
        $html =
            '<div><label class="mb-1 block text-sm font-semibold" for="f_' .
            $name .
            '">' .
            e($label) .
            '</label><select id="f_' .
            $name .
            '" name="' .
            $name .
            '" class="field"><option value="">—</option>';
        foreach ($options as $o) {
            $html .= '<option value="' . e($o) . '"' . ($current === $o ? ' selected' : '') . '>' . e($o) . '</option>';
        }
        return new \Illuminate\Support\HtmlString($html . '</select></div>');
    };
    $pageTitle = $activeCategory?->name ?? __('site.products');
@endphp
<x-layouts.public-shell :title="$activeCategory?->seo_title ?: $pageTitle" :description="$activeCategory?->seo_description ?: $activeCategory?->description" :breadcrumbs="$crumbs" :noindex="count(array_filter(request()->except('page'))) > 1" :canonical="$lr('catalog.index').($activeCategory ? '?category='.$activeCategory->slug : '')">

    <section class="relative isolate overflow-hidden bg-gradient-to-br from-navy-deep via-navy to-industrial text-white">
        <div class="bg-grid absolute inset-0 -z-10 opacity-60" aria-hidden="true"></div>
        <div class="container-x py-14">
            <p class="text-xs font-bold uppercase tracking-[0.2em] text-amber">{{ __('site.products') }}</p>
            <h1 class="mt-2 text-4xl font-extrabold tracking-tight sm:text-5xl">{{ $pageTitle }}</h1>
            @if ($activeCategory?->description)
                <p class="mt-3 max-w-2xl text-white/75">{{ $activeCategory->description }}</p>
            @endif
            <p class="mt-4 text-sm text-white/60" role="status">{{ $products->total() }} · {{ __('site.products') }}</p>
        </div>
    </section>

    <div class="container-x py-10" x-data="{ open: false }">
        <button type="button" class="btn btn-outline mb-6 w-full lg:hidden" x-on:click="open = !open"
            :aria-expanded="open">{{ __('site.filters') }}</button>

        <div class="grid gap-8 lg:grid-cols-[17rem_1fr]">
            <form method="GET" action="{{ $lr('catalog.index') }}"
                class="card hidden space-y-4 self-start p-5 lg:sticky lg:top-24 lg:block" :class="open && '!block'"
                aria-label="{{ __('site.filters') }}">
                <h2 class="text-lg font-extrabold text-navy">{{ __('site.filters') }}</h2>
                <div><label class="mb-1 block text-sm font-semibold"
                        for="f_q">{{ __('site.search') }}</label><input id="f_q" name="q"
                        value="{{ $filters['q'] ?? '' }}" class="field"></div>
                <div><label class="mb-1 block text-sm font-semibold" for="f_category">{{ __('site.category') }}</label>
                    <select id="f_category" name="category" class="field">
                        <option value="">—</option>
                        @foreach ($categories as $c)
                            <option value="{{ $c->slug }}" @selected(($filters['category'] ?? '') === $c->slug)>{{ $c->name }}
                            </option>
                            @foreach ($c->children as $child)
                                <option value="{{ $child->slug }}" @selected(($filters['category'] ?? '') === $child->slug)>— {{ $child->name }}
                                </option>
                            @endforeach
                        @endforeach
                    </select>
                </div>
                {!! $select('application', __('site.application'), $applications, $filters['application'] ?? null) !!}
                {!! $select('viscosity', __('site.viscosity'), $viscosities, $filters['viscosity'] ?? null) !!}
                {!! $select('api', __('site.api'), $apis, $filters['api'] ?? null) !!}
                {!! $select('packaging', __('site.packaging'), $packs, $filters['packaging'] ?? null) !!}
                <div><label class="mb-1 block text-sm font-semibold" for="f_sort">Sort</label>
                    <select id="f_sort" name="sort" class="field">
                        <option value="newest">Newest</option>
                        <option value="sku" @selected(($filters['sort'] ?? '') === 'sku')>SKU</option>
                    </select>
                </div>
                <div class="flex items-center gap-4"><button
                        class="btn btn-dark !py-2.5">{{ __('site.apply') }}</button><a
                        href="{{ $lr('catalog.index') }}"
                        class="font-semibold text-industrial underline">{{ __('site.reset') }}</a></div>
            </form>

            <div>
                @if ($products->isEmpty())
                    <div class="card p-10 text-center">
                        <p class="text-lg font-bold text-navy">{{ __('site.no_results') }}</p>
                        <a href="{{ $lr('catalog.index') }}" class="btn btn-dark mt-5">{{ __('site.reset') }}</a>
                    </div>
                @else
                    <div class="grid gap-6 sm:grid-cols-2 xl:grid-cols-3">
                        @foreach ($products as $product)
                            @include('catalog._card', ['product' => $product])
                        @endforeach
                    </div>
                    <div class="mt-10">{{ $products->links() }}</div>
                @endif
            </div>
        </div>
    </div>
</x-layouts.public-shell>
