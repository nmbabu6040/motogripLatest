@php $img = $product->getFirstMedia('images'); $packs = $product->packaging->where('is_active', true); @endphp
<article class="group card flex flex-col overflow-hidden transition duration-300 hover:-translate-y-1 hover:shadow-lift motion-reduce:transition-none motion-reduce:hover:translate-y-0">
    <a href="{{ $lr('catalog.show', ['product' => $product]) }}" class="relative block aspect-[4/5] overflow-hidden bg-gradient-to-b from-white to-surface">
        @if ($img)
            <img src="{{ $img->hasGeneratedConversion('card') ? $img->getUrl('card') : $img->getUrl() }}" alt="{{ $img->getCustomProperty('alt') ?: $product->name }}" loading="lazy" width="480" height="600" class="h-full w-full object-contain p-4 transition-transform duration-500 group-hover:scale-105 motion-reduce:transition-none">
        @else
            <span class="flex h-full items-center justify-center text-sm text-ink/50">{{ $product->sku }}</span>
        @endif
        @if ($product->category)<span class="absolute left-3 top-3 rounded-full bg-navy/90 px-2.5 py-1 text-xs font-semibold text-white">{{ $product->category->name }}</span>@endif
    </a>
    <div class="flex flex-1 flex-col p-5">
        <h3 class="text-lg font-extrabold leading-snug tracking-tight text-navy"><a href="{{ $lr('catalog.show', ['product' => $product]) }}" class="hover:underline">{{ $product->name }}</a></h3>
        <p class="mt-1 text-xs font-semibold text-ink/60">{{ __('site.sku') }}: {{ $product->sku }}@if ($product->viscosity_grade) · {{ $product->viscosity_grade }}@endif</p>
        @if ($product->short_description)<p class="mt-3 line-clamp-2 text-sm leading-relaxed">{{ $product->short_description }}</p>@endif
        @if ($packs->isNotEmpty())<ul class="mt-4 flex flex-wrap gap-1.5">@foreach ($packs->take(4) as $pack)<li class="chip !px-2.5 !py-0.5 text-xs">{{ $pack->label }}</li>@endforeach</ul>@endif
        <div class="mt-auto flex items-center justify-between gap-2 border-t border-steel/60 pt-4 text-sm" x-data>
            <a href="{{ $lr('contact.create') }}?product={{ $product->slug }}" class="font-bold text-industrial hover:underline">{{ __('site.send_inquiry') }} →</a>
            <label class="flex cursor-pointer items-center gap-1.5 text-ink/70"><input type="checkbox" class="accent-navy" :checked="$store.compare.has('{{ $product->slug }}')" x-on:change="$store.compare.toggle('{{ $product->slug }}')"> {{ __('site.compare') }}</label>
        </div>
    </div>
</article>
