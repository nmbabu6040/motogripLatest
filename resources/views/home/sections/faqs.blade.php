@if ($faqs->isNotEmpty())
<section class="py-20" data-reveal><div class="container-x max-w-3xl">
    <h2 class="section-title text-center">{{ __('site.faqs') }}</h2>
    <span class="bar mx-auto" aria-hidden="true"></span>
    <div class="mt-10 space-y-3">
        @foreach ($faqs as $f)
            <details class="card group p-5"><summary class="flex cursor-pointer list-none items-center justify-between gap-4 font-bold text-navy">{{ \App\Support\Lang::pick($f->question) }}<span class="text-amber transition group-open:rotate-45" aria-hidden="true">＋</span></summary><p class="mt-3 leading-relaxed">{{ \App\Support\Lang::pick($f->answer) }}</p></details>
        @endforeach
    </div>
</div></section>
@endif
