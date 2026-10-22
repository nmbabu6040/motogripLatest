<x-layouts.public-shell :title="$activeCategory?->name ?? __('site.blog')" :noindex="request()->filled('q') || request()->filled('tag')" :canonical="$lr('blog.index').($activeCategory ? '?category='.$activeCategory->slug : '')" :breadcrumbs="[[__('site.home'), $lr('home')], [__('site.blog'), $lr('blog.index')]]">
    <x-page-header :title="$activeTag ? '#'.$activeTag->name : ($activeCategory?->name ?? __('site.blog'))" :eyebrow="__('site.blog')" :subtitle="__('site.blog_intro')">
        <form method="GET" role="search" class="mt-6 flex max-w-xl flex-col gap-3 sm:flex-row"><label for="bq" class="sr-only">{{ __('site.search') }}</label>
            <input id="bq" name="q" value="{{ request('q') }}" placeholder="{{ __('site.search') }}" class="flex-1 rounded-control border-0 bg-white px-4 py-3 text-ink placeholder:text-ink/60"><button class="btn btn-primary">{{ __('site.search') }}</button></form>
    </x-page-header>

    <div class="container-x py-12">
        <nav aria-label="Categories" class="mb-8 flex flex-wrap gap-2 text-sm font-semibold">
            <a href="{{ $lr('blog.index') }}" class="chip {{ ! $activeCategory ? '!border-navy !bg-navy text-white' : 'hover:border-navy' }}">{{ __('site.all') }}</a>
            @foreach ($categories as $c)<a href="{{ $lr('blog.index') }}?category={{ $c->slug }}" class="chip {{ $activeCategory?->id === $c->id ? '!border-navy !bg-navy text-white' : 'hover:border-navy' }}">{{ $c->name }}</a>@endforeach
        </nav>
        @if ($posts->isEmpty())
            <div class="card p-10 text-center"><p class="text-lg font-bold text-navy">{{ __('site.no_results') }}</p></div>
        @else
            <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">@foreach ($posts as $post)@include('blog._card', ['post' => $post])@endforeach</div>
            <div class="mt-10">{{ $posts->links() }}</div>
        @endif
    </div>
</x-layouts.public-shell>
