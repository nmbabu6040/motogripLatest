@if ($documents->isNotEmpty())
<section class="py-20" data-reveal><div class="container-x">
    <div class="flex flex-wrap items-end justify-between gap-4"><div><p class="eyebrow">{{ __('site.documents') }}</p><h2 class="section-title">{{ __('site.resources') }}</h2><span class="bar" aria-hidden="true"></span></div><a class="font-semibold text-industrial underline underline-offset-4" href="{{ $lr('resources.index') }}">{{ __('site.all_resources') }} →</a></div>
    <ul class="mt-10 grid gap-4 sm:grid-cols-2">@foreach ($documents as $d)<li class="card flex items-center justify-between gap-4 p-5"><span class="font-semibold text-navy">{{ $d->title }}</span><a class="btn btn-dark !px-4 !py-2 text-sm" href="{{ route('documents.download', $d) }}">{{ __('site.download') }}</a></li>@endforeach</ul>
</div></section>
@endif
