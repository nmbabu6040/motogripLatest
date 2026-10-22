@php $img = $post->getFirstMedia('featured'); @endphp
<article class="group card flex flex-col overflow-hidden transition duration-300 hover:-translate-y-1 hover:shadow-lift motion-reduce:transition-none motion-reduce:hover:translate-y-0">
    <a href="{{ $lr('blog.show', ['post' => $post]) }}" class="block aspect-video overflow-hidden bg-surface">
        @if ($img)<img src="{{ $img->hasGeneratedConversion('card') ? $img->getUrl('card') : $img->getUrl() }}" alt="{{ $img->getCustomProperty('alt') ?: '' }}" loading="lazy" width="640" height="360" class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105 motion-reduce:transition-none">
        @else<span class="flex h-full items-center justify-center bg-gradient-to-br from-navy to-industrial text-3xl font-extrabold text-amber/80" aria-hidden="true">{{ mb_strtoupper(mb_substr($post->title, 0, 1)) }}</span>@endif
    </a>
    <div class="flex flex-1 flex-col p-6">
        <p class="eyebrow !tracking-wider">{{ $post->category?->name }}@if ($post->category) · @endif{{ $post->published_at?->translatedFormat('j M Y') }}</p>
        <h3 class="mt-2 text-xl font-extrabold leading-snug tracking-tight text-navy"><a href="{{ $lr('blog.show', ['post' => $post]) }}" class="hover:underline">{{ $post->title }}</a></h3>
        @if ($post->excerpt)<p class="mt-3 line-clamp-3 text-sm leading-relaxed">{{ $post->excerpt }}</p>@endif
        <p class="mt-auto pt-4 text-xs font-semibold text-ink/60">{{ $post->reading_time_minutes }} {{ __('site.min_read') }}</p>
    </div>
</article>
