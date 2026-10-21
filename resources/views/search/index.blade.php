<x-layouts.public-shell :title="__('site.search_results')" :noindex="true">
    <x-page-header :title="__('site.search_results')">
        <form method="GET" action="{{ $lr('search') }}" role="search" class="mt-6 flex max-w-xl flex-col gap-3 sm:flex-row">
            <label for="sq" class="sr-only">{{ __('site.search') }}</label>
            <input id="sq" name="q" value="{{ $q }}" class="flex-1 rounded-control border-0 bg-white px-4 py-3 text-ink">
            <button class="btn btn-primary">{{ __('site.search') }}</button>
        </form>
    </x-page-header>
    <div class="container-x py-12">
        @if ($q !== '' && $products->isEmpty() && $posts->isEmpty())
            <div class="card p-10 text-center"><p class="text-lg font-bold text-navy">{{ __('site.no_results') }}</p>
                <div class="mt-5 flex flex-wrap justify-center gap-3"><a class="btn btn-dark" href="{{ $lr('catalog.index') }}">{{ __('site.all_products') }}</a><a class="btn btn-outline" href="{{ $lr('contact.create') }}">{{ __('site.contact_sales') }}</a></div></div>
        @endif
        @if ($products->isNotEmpty())
            <h2 class="section-title !mt-0">{{ __('site.products') }}</h2><span class="bar" aria-hidden="true"></span>
            <div class="mt-8 grid gap-6 sm:grid-cols-2 lg:grid-cols-4">@foreach ($products as $product)@include('catalog._card', ['product' => $product->load(['media', 'packaging'])])@endforeach</div>
        @endif
        @if ($posts->isNotEmpty())
            <h2 class="section-title">{{ __('site.blog') }}</h2><span class="bar" aria-hidden="true"></span>
            <ul class="mt-6 space-y-3">@foreach ($posts as $post)<li><a class="text-lg font-bold text-industrial hover:underline" href="{{ $lr('blog.show', ['post' => $post]) }}">{{ $post->title }}</a></li>@endforeach</ul>
        @endif
    </div>
</x-layouts.public-shell>
