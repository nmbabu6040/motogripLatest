<x-layouts.public-shell :title="__('site.resources')" :breadcrumbs="[[__('site.home'), $lr('home')], [__('site.resources'), $lr('resources.index')]]">
    <x-page-header :title="__('site.resources')" :eyebrow="__('site.documents')" :subtitle="__('site.resources_intro')">
        <form method="GET" class="mt-6 flex max-w-2xl flex-col gap-3 sm:flex-row" role="search">
            <label class="sr-only" for="q">{{ __('site.search') }}</label><input id="q" name="q" value="{{ request('q') }}" placeholder="{{ __('site.search') }}" class="flex-1 rounded-control border-0 bg-white px-4 py-3 text-ink placeholder:text-ink/60">
            <label class="sr-only" for="type">Type</label>
            <select id="type" name="type" class="rounded-control border-0 bg-white px-4 py-3 text-ink"><option value="">{{ __('site.all') }}</option>@foreach ($types as $k => $v)<option value="{{ $k }}" @selected($activeType === $k)>{{ $v }}</option>@endforeach</select>
            <button class="btn btn-primary">{{ __('site.apply') }}</button>
        </form>
    </x-page-header>

    <div class="container-x py-12">
        @if ($documents->isEmpty())
            <div class="card p-10 text-center"><p class="text-lg font-bold text-navy">{{ __('site.no_results') }}</p></div>
        @else
            <ul class="grid gap-4 md:grid-cols-2">
                @foreach ($documents as $d)
                    <li class="card flex items-center gap-4 p-5">
                        <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-control bg-navy text-xs font-extrabold text-amber">PDF</span>
                        <div class="min-w-0 flex-1">
                            <p class="font-extrabold text-navy">{{ $d->title }}</p>
                            <p class="text-sm text-ink/70">{{ $types[$d->type] ?? $d->type }}@if ($d->product) · {{ $d->product->name }}@endif @if ($d->version) · v{{ $d->version }}@endif · {{ number_format($d->size / 1048576, 1) }} MB</p>
                        </div>
                        <a href="{{ route('documents.download', $d) }}" class="btn btn-dark !px-4 !py-2">{{ __('site.download') }}@if ($d->visibility !== 'public')<span class="text-xs font-normal"> ({{ __('site.sign_in_required') }})</span>@endif</a>
                    </li>
                @endforeach
            </ul>
            <div class="mt-8">{{ $documents->links() }}</div>
        @endif
    </div>
</x-layouts.public-shell>
