@if ($posts->isNotEmpty())
    <section class="bg-surface py-20" data-reveal>
        <div class="container-x">
            <div class="flex flex-wrap items-end justify-between gap-4">
                <div>
                    <p class="eyebrow">{{ __('site.blog') }}</p>
                    <h2 class="section-title">{{ __('site.latest_articles') }}</h2>
                    <span class="bar" aria-hidden="true"></span>
                </div>
                <a class="font-semibold text-industrial underline underline-offset-4"
                    href="{{ $lr('blog.index') }}">{{ __('site.blog') }} →</a>
            </div>
            <div class="mt-10 grid gap-6 md:grid-cols-3">
                @foreach ($posts as $post)
                    @include('blog._card', ['post' => $post])
                @endforeach
            </div>
        </div>
    </section>
@endif
