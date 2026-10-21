<x-layouts.public-shell :title="__('site.compare')" :noindex="true" :breadcrumbs="[[__('site.home'), $lr('home')], [__('site.compare'), $lr('compare')]]">
    <x-page-header :title="__('site.compare')" :eyebrow="__('site.products')" :subtitle="__('site.compare_intro')" />
    <div class="container-x py-10">
        @if ($products->count() < 2)
            <div class="card p-10 text-center">
                <p class="text-lg font-bold text-navy">{{ __('site.compare_need_two') }}</p><a class="btn btn-dark mt-5"
                    href="{{ $lr('catalog.index') }}">{{ __('site.explore_products') }}</a>
            </div>
        @else
            <p class="mb-4 text-sm">{{ __('site.compare_hint') }}</p>
            <div class="card overflow-x-auto">
                <table class="w-full min-w-[40rem] text-left text-sm">
                    <thead class="bg-surface">
                        <tr>
                            <th class="p-4"><span class="sr-only">Property</span></th>
                            @foreach ($products as $p)
                                <th class="p-4 align-top"><a class="text-base font-extrabold text-navy hover:underline"
                                        href="{{ $lr('catalog.show', ['product' => $p]) }}">{{ $p->name }}</a>
                                    <p class="font-normal text-ink/70">{{ $p->sku }}</p>
                                    <a class="font-normal text-industrial underline"
                                        href="{{ $lr('compare') }}?{{ http_build_query(['p' => $products->where('slug', '!=', $p->slug)->pluck('slug')->all()]) }}">Remove</a>
                                </th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($table as $row)
                            <tr class="border-t border-steel/60 {{ $row['differs'] ? 'bg-amber/15' : '' }}">
                                <th scope="row" class="p-4 font-bold text-navy">{{ $row['label'] }}@if ($row['differs'])
                                        <span class="sr-only"> (differs)</span>
                                    @endif
                                </th>
                                @foreach ($row['values'] as $v)
                                    <td class="p-4">{{ $v ?? '—' }}</td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</x-layouts.public-shell>
