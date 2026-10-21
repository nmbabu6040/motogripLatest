@php
    $img = $post->getFirstMedia('featured');
    $imageUrl = $img?->getUrl();
    $profile = \App\Models\AuthorProfile::where('user_id', $post->author_id)->first();
    $url = $lr('blog.show', ['post' => $post]);
    $siteName = \App\Support\Settings::get('brand', 'site_name', config('app.name'));
    $ld = [
        [
            '@context' => 'https://schema.org',
            '@type' => 'Article',
            'headline' => $post->title,
            'description' => $post->seo_description ?: $post->excerpt,
            'image' => $imageUrl,
            'datePublished' => $post->published_at?->toIso8601String(),
            'dateModified' => $post->updated_at->toIso8601String(),
            'author' => ['@type' => 'Person', 'name' => $post->author?->name],
            'publisher' => ['@type' => 'Organization', 'name' => $siteName],
            'mainEntityOfPage' => $url,
            'inLanguage' => app()->getLocale(),
        ],
    ];
    $crumbs = [[__('site.home'), $lr('home')], [__('site.blog'), $lr('blog.index')], [$post->title, $url]];
@endphp
<x-layouts.public-shell :title="$post->seo_title ?: $post->title" :description="$post->seo_description ?: $post->excerpt" :image="$imageUrl" type="article" :noindex="$post->noindex || !$post->isPublished()"
    :canonical="$post->canonical_url ?: $url" :jsonld="$ld" :breadcrumbs="$crumbs">

    <header class="relative isolate overflow-hidden bg-gradient-to-br from-navy-deep via-navy to-industrial text-white">
        <div class="bg-grid absolute inset-0 -z-10 opacity-60" aria-hidden="true"></div>
        <div class="container-x max-w-4xl py-12 sm:py-16">
            @unless ($post->isPublished())
                <p class="mb-4 inline-block rounded-control bg-amber/30 px-3 py-1 text-sm font-semibold">Preview: this
                    article is not public.</p>
            @endunless
            @if ($post->category)
                <p class="text-xs font-bold uppercase tracking-[0.2em] text-amber">{{ $post->category->name }}</p>
            @endif
            <h1 class="mt-3 text-3xl font-extrabold leading-tight tracking-tight sm:text-5xl">{{ $post->title }}</h1>
            <p class="mt-5 text-sm text-white/70">{{ __('site.by') }} {{ $post->author?->name }} ·
                {{ $post->published_at?->translatedFormat('j F Y') }} · {{ $post->reading_time_minutes }}
                {{ __('site.min_read') }}</p>
        </div>
    </header>

    <article class="container-x max-w-4xl py-10">
        @if ($img)
            <img src="{{ $img->hasGeneratedConversion('large') ? $img->getUrl('large') : $img->getUrl() }}"
                alt="{{ $img->getCustomProperty('alt') ?: '' }}" width="1400" height="788" fetchpriority="high"
                class="-mt-20 w-full rounded-panel shadow-lift ring-1 ring-steel/50 sm:-mt-24">
        @endif

        <x-ad place="in_article" />
        <div
            class="mx-auto mt-8 max-w-3xl text-lg leading-relaxed [&_a]:text-industrial [&_a]:underline [&_blockquote]:border-l-4 [&_blockquote]:border-amber [&_blockquote]:pl-4 [&_blockquote]:italic [&_h2]:mt-10 [&_h2]:text-2xl [&_h2]:font-extrabold [&_h2]:text-navy [&_h3]:mt-8 [&_h3]:text-xl [&_h3]:font-bold [&_h3]:text-navy [&_img]:max-w-full [&_img]:rounded-control [&_ol]:mt-4 [&_ol]:list-decimal [&_ol]:pl-6 [&_p]:mt-5 [&_ul]:mt-4 [&_ul]:list-disc [&_ul]:pl-6">
            {!! $post->body !!}</div>

        <div class="mx-auto mt-10 max-w-3xl">
            @if ($post->tags->isNotEmpty())
                <p class="flex flex-wrap items-center gap-2 text-sm"><span
                        class="font-semibold">{{ __('site.tags') }}:</span>
                    @foreach ($post->tags as $t)
                        <a class="chip font-semibold hover:border-navy"
                            href="{{ $lr('blog.index') }}?tag={{ $t->slug }}">#{{ $t->name }}</a>
                    @endforeach
                </p>
            @endif

            <div class="mt-6 flex flex-wrap items-center gap-6 border-y border-steel/70 py-5">
                <form method="POST" action="{{ $lr('blog.like', ['post' => $post]) }}">
                    @csrf
                    <button class="btn !px-5 !py-2.5 {{ $liked ? 'btn-dark' : 'btn-outline' }}"
                        aria-pressed="{{ $liked ? 'true' : 'false' }}">{{ $liked ? __('site.unlike') : __('site.like') }}
                        · {{ $post->likes_count }}</button>
                </form>
                <x-share :url="$url" :title="$post->title" />
            </div>

            @if ($profile?->bio || $post->author)
                <aside class="mt-8 flex gap-4 rounded-panel bg-surface p-6 text-sm">
                    <span
                        class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-navy text-lg font-bold text-amber">{{ mb_strtoupper(mb_substr($post->author?->name ?? '?', 0, 1)) }}
                    </span>
                    <div>
                        <p class="font-extrabold text-navy">{{ $post->author?->name }}</p>
                        @if ($profile?->bio)
                            <p class="mt-1">{{ $profile->bio }}</p>
                        @endif
                    </div>
                </aside>
            @endif

            <section class="mt-12" aria-labelledby="comments">
                <h2 id="comments" class="text-2xl font-extrabold text-navy">{{ __('site.comments') }}
                    ({{ $post->comments_count }})</h2>
                @if ($errors->any())
                    <div role="alert"
                        class="mt-3 rounded-control border border-red-300 bg-red-50 p-3 text-sm text-red-900">
                        <ul class="list-disc pl-5">
                            @foreach ($errors->all() as $e)
                                <li>{{ $e }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
                @if (config('motogrip.features.guest_comments') || auth()->check())
                    <form method="POST" action="{{ $lr('blog.comment', ['post' => $post]) }}"
                        class="card mt-5 space-y-3 p-5">
                        @csrf
                        @guest
                            <div class="grid gap-3 sm:grid-cols-2">
                                <x-field name="guest_name" :label="__('site.name')" required />
                                <x-field name="guest_email" :label="__('site.email')" type="email" required />
                            </div>
                        @endguest
                        <div class="hidden" aria-hidden="true">
                            <input name="website" tabindex="-1" autocomplete="off">
                        </div>
                        <label class="block text-sm font-semibold"
                            for="cbody">{{ __('site.leave_comment') }}</label>
                        <textarea id="cbody" name="body" rows="4" required class="field">{{ old('body') }}</textarea>
                        <button class="btn btn-dark !py-2.5">{{ __('site.submit') }}</button>
                    </form>
                @endif
                <ul class="mt-6 space-y-4">
                    @foreach ($comments as $comment)
                        @include('blog._comment', ['comment' => $comment, 'depth' => 0, 'post' => $post])
                    @endforeach
                </ul>
            </section>
        </div>

        @if ($related->isNotEmpty())
            <section class="mt-16">
                <p class="eyebrow">{{ __('site.blog') }}</p>
                <h2 class="section-title">{{ __('site.related') }}</h2><span class="bar" aria-hidden="true"></span>
                <div class="mt-8 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($related as $rp)
                        @include('blog._card', ['post' => $rp])
                    @endforeach
                </div>
            </section>
        @endif
    </article>
</x-layouts.public-shell>
