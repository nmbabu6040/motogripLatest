@php
    $images = $product->getMedia('images');
    $first = $images->first();
    $siteName = \App\Support\Settings::get('brand', 'site_name', config('app.name'));
    $url = $lr('catalog.show', ['product' => $product]);
    $crumbs = array_values(array_filter([[__('site.home'), $lr('home')], [__('site.products'), $lr('catalog.index')], $product->category ? [$product->category->name, $lr('catalog.index').'?category='.$product->category->slug] : null, [$product->name, $url]]));
    $imageUrl = $first ? $first->getUrl() : null;
    $packs = $product->packaging->where('is_active', true);
    $wa = \App\Support\Settings::get('contact', 'whatsapp');
    $waLink = $wa ? 'https://wa.me/'.$wa.'?text='.urlencode('Hello, I am interested in '.$product->name.' ('.$product->sku.')') : null;

    // Product schema carries only data an admin entered. No offers, ratings or reviews are ever invented.
    $ld = [['@context' => 'https://schema.org', '@type' => 'Product', 'name' => $product->name, 'sku' => $product->sku,
        'description' => $product->short_description ?: null, 'image' => $imageUrl, 'category' => $product->category?->name,
        'brand' => ['@type' => 'Brand', 'name' => $siteName]]];

    $featureKinds = ['feature' => 'features', 'benefit' => 'benefits', 'application' => 'applications'];
    $hasOverview = $product->description || $product->features->isNotEmpty();
    $tabs = array_filter([
        'overview' => $hasOverview ? __('site.overview') : null,
        'specs' => ($product->specifications->isNotEmpty() || $product->approvals->isNotEmpty()) ? __('site.specifications') : null,
        'docs' => $product->documents->isNotEmpty() ? __('site.documents') : null,
        'handling' => $product->handling_storage ? __('site.handling') : null,
    ]);
    $firstTab = array_key_first($tabs) ?? 'overview';
@endphp
<x-layouts.public-shell :title="$product->seo_title ?: $product->name" :description="$product->seo_description ?: $product->short_description" :image="$imageUrl" :noindex="$product->noindex || ! $product->isPublished()"
    :canonical="$product->canonical_url ?: $url" type="product" :jsonld="$ld" :breadcrumbs="$crumbs">
    <article class="container-x pb-28 pt-10 md:pb-16">
        @unless ($product->isPublished())<p class="mb-6 rounded-control bg-amber/30 p-3 text-sm font-semibold">Preview: this product is not public.</p>@endunless

        <div class="grid gap-10 lg:grid-cols-[1.05fr_0.95fr] lg:gap-14">
            {{-- Gallery --}}
            <div class="self-start lg:sticky lg:top-24" x-data="{ i: 0 }">
                <div class="card relative aspect-square overflow-hidden bg-gradient-to-b from-white to-surface">
                    @if ($images->isNotEmpty())
                        @foreach ($images as $k => $m)
                            <img x-show="i === {{ $k }}" @if ($k) x-cloak loading="lazy" @endif src="{{ $m->hasGeneratedConversion('large') ? $m->getUrl('large') : $m->getUrl() }}" alt="{{ $m->getCustomProperty('alt') ?: $product->name }}" width="1200" height="1200" class="h-full w-full object-contain p-6">
                        @endforeach
                    @else
                        <div class="flex h-full items-center justify-center text-ink/50">{{ $product->sku }}</div>
                    @endif
                    @if ($product->viscosity_grade)<span class="absolute left-4 top-4 rounded-full bg-amber px-3 py-1 text-sm font-extrabold text-navy">{{ $product->viscosity_grade }}</span>@endif
                </div>
                @if ($images->count() > 1)
                    <ul class="mt-3 flex gap-2">@foreach ($images as $k => $m)
                        <li><button type="button" x-on:click="i = {{ $k }}" :class="i === {{ $k }} ? 'ring-2 ring-navy' : 'ring-1 ring-steel'" class="block h-16 w-16 overflow-hidden rounded-control bg-white" aria-label="Image {{ $k + 1 }}"><img src="{{ $m->hasGeneratedConversion('card') ? $m->getUrl('card') : $m->getUrl() }}" alt="" loading="lazy" width="64" height="64" class="h-full w-full object-contain p-1"></button></li>
                    @endforeach</ul>
                @endif
            </div>

            {{-- Key information --}}
            <div>
                @if ($product->category)<a href="{{ $lr('catalog.index') }}?category={{ $product->category->slug }}" class="eyebrow hover:underline">{{ $product->category->name }}</a>@endif
                <h1 class="mt-2 text-3xl font-extrabold leading-tight tracking-tight text-navy sm:text-4xl">{{ $product->name }}</h1>
                <p class="mt-2 text-sm font-semibold text-ink/60">{{ __('site.sku') }}: {{ $product->sku }}</p>
                <span class="bar" aria-hidden="true"></span>

                @if ($product->short_description)<p class="mt-6 text-lg leading-relaxed">{{ $product->short_description }}</p>@endif

                <dl class="mt-6 grid grid-cols-2 gap-3">
                    @foreach (['application' => __('site.application'), 'viscosity_grade' => __('site.viscosity'), 'api_classification' => __('site.api'), 'iso_vg_grade' => __('site.iso_vg')] as $f => $label)
                        @if ($product->$f)<div class="rounded-control border border-steel/70 bg-surface px-4 py-3"><dt class="text-xs font-bold uppercase tracking-wider text-ink/60">{{ $label }}</dt><dd class="mt-0.5 font-bold text-navy">{{ $product->$f }}</dd></div>@endif
                    @endforeach
                </dl>

                @if ($packs->isNotEmpty())
                    <h2 class="mt-6 text-sm font-bold uppercase tracking-wider text-ink/60">{{ __('site.packaging') }}</h2>
                    <ul class="mt-2 flex flex-wrap gap-2">@foreach ($packs as $p)<li class="chip font-semibold">{{ $p->label }}</li>@endforeach</ul>
                @endif

                <div class="mt-8 flex flex-wrap gap-3" x-data>
                    <a href="{{ $lr('contact.create') }}?product={{ $product->slug }}" class="btn btn-primary">{{ __('site.request_quote') }} <span aria-hidden="true">→</span></a>
                    @if ($waLink)<a href="{{ $waLink }}" target="_blank" rel="noopener" class="btn btn-outline">WhatsApp</a>@endif
                    <a href="{{ $lr('dealers.index') }}" class="btn btn-outline">{{ __('site.find_dealer') }}</a>
                </div>
                <label class="mt-5 inline-flex cursor-pointer items-center gap-2 text-sm font-semibold text-ink/80" x-data>
                    <input type="checkbox" class="accent-navy" :checked="$store.compare.has('{{ $product->slug }}')" x-on:change="$store.compare.toggle('{{ $product->slug }}')"> {{ __('site.compare') }}
                </label>
                <div class="mt-6 border-t border-steel/60 pt-5"><x-share :url="$url" :title="$product->name" /></div>
            </div>
        </div>

        {{-- Detail tabs --}}
        @if (count($tabs))
        <section class="mt-14" x-data="{ tab: '{{ $firstTab }}' }">
            <div role="tablist" aria-label="{{ $product->name }}" class="flex flex-wrap gap-2 border-b border-steel">
                @foreach ($tabs as $key => $label)
                    <button type="button" role="tab" id="tab-{{ $key }}" aria-controls="panel-{{ $key }}" :aria-selected="tab === '{{ $key }}'" x-on:click="tab = '{{ $key }}'"
                            :class="tab === '{{ $key }}' ? 'border-amber text-navy' : 'border-transparent text-ink/60 hover:text-navy'"
                            class="-mb-px border-b-4 px-4 py-3 font-bold transition-colors">{{ $label }}</button>
                @endforeach
            </div>

            @if (isset($tabs['overview']))
            <div id="panel-overview" role="tabpanel" aria-labelledby="tab-overview" x-show="tab === 'overview'" class="pt-8">
                @if ($product->description)<div class="max-w-3xl whitespace-pre-line text-lg leading-relaxed">{{ $product->description }}</div>@endif
                @if ($product->features->isNotEmpty())
                    <div class="mt-8 grid gap-5 md:grid-cols-3">
                        @foreach ($featureKinds as $kind => $key)
                            @php $items = $product->features->where('kind', $kind); @endphp
                            @if ($items->isNotEmpty())
                                <div class="card border-t-4 border-t-amber p-6"><h2 class="text-lg font-extrabold text-navy">{{ __("site.$key") }}</h2>
                                    <ul class="mt-3 space-y-2">@foreach ($items as $f)<li class="flex gap-2"><span class="mt-1.5 h-2 w-2 shrink-0 rounded-full bg-amber" aria-hidden="true"></span><span>{{ $f->text }}</span></li>@endforeach</ul></div>
                            @endif
                        @endforeach
                    </div>
                @endif
            </div>
            @endif

            @if (isset($tabs['specs']))
            <div id="panel-specs" role="tabpanel" aria-labelledby="tab-specs" x-show="tab === 'specs'" @if ($firstTab !== 'specs') x-cloak @endif class="pt-8">
                @if ($product->specifications->isNotEmpty())
                    <div class="card max-w-3xl overflow-x-auto"><table class="w-full text-left text-sm">
                        <thead class="bg-surface text-xs uppercase tracking-wider text-ink/70"><tr><th class="p-4">Property</th><th class="p-4">Value</th><th class="p-4">Test method</th><th class="p-4">Basis</th></tr></thead>
                        <tbody>@foreach ($product->specifications as $s)<tr class="border-t border-steel/60"><td class="p-4 font-semibold">{{ $s->name }}</td><td class="p-4">{{ $s->value }} {{ $s->unit }}</td><td class="p-4">{{ $s->test_method ?: '—' }}</td><td class="p-4">{{ $s->is_typical ? __('site.typical') : __('site.guaranteed') }}</td></tr>@endforeach</tbody>
                    </table></div>
                @endif
                @if ($product->approvals->isNotEmpty())
                    <h2 class="mt-8 text-lg font-extrabold text-navy">{{ __('site.approvals') }}</h2>
                    <ul class="mt-3 flex flex-wrap gap-2">@foreach ($product->approvals as $a)<li class="chip font-semibold">{{ $a->name }}@if ($a->reference) ({{ $a->reference }})@endif</li>@endforeach</ul>
                @endif
            </div>
            @endif

            @if (isset($tabs['docs']))
            <div id="panel-docs" role="tabpanel" aria-labelledby="tab-docs" x-show="tab === 'docs'" @if ($firstTab !== 'docs') x-cloak @endif class="pt-8">
                <ul class="max-w-2xl divide-y divide-steel/60 card">@foreach ($product->documents as $d)
                    <li class="flex items-center justify-between gap-4 p-4"><span><span class="font-bold text-navy">{{ \App\Models\Document::TYPES[$d->type] ?? $d->type }}</span><br><span class="text-sm">{{ $d->title }}@if ($d->visibility !== 'public') · {{ __('site.sign_in_required') }}@endif</span></span>
                        <a href="{{ route('documents.download', $d) }}" class="btn btn-dark !px-4 !py-2">{{ __('site.download') }}</a></li>
                @endforeach</ul>
            </div>
            @endif

            @if (isset($tabs['handling']))
            <div id="panel-handling" role="tabpanel" aria-labelledby="tab-handling" x-show="tab === 'handling'" @if ($firstTab !== 'handling') x-cloak @endif class="pt-8"><div class="max-w-3xl whitespace-pre-line text-lg leading-relaxed">{{ $product->handling_storage }}</div></div>
            @endif
        </section>
        @endif

        @if ($product->related->isNotEmpty())
            <section class="mt-16"><p class="eyebrow">{{ __('site.products') }}</p><h2 class="section-title">{{ __('site.related') }}</h2><span class="bar" aria-hidden="true"></span>
                <div class="mt-8 grid gap-6 sm:grid-cols-2 lg:grid-cols-4">@foreach ($product->related as $rel)@include('catalog._card', ['product' => $rel->load(['category', 'packaging'])])@endforeach</div></section>
        @endif
    </article>

    {{-- Phone-only sticky quote bar --}}
    <div class="fixed inset-x-0 bottom-0 z-30 border-t border-steel bg-white/95 p-3 backdrop-blur md:hidden">
        <a href="{{ $lr('contact.create') }}?product={{ $product->slug }}" class="btn btn-primary w-full">{{ __('site.request_quote') }}</a>
    </div>
</x-layouts.public-shell>
