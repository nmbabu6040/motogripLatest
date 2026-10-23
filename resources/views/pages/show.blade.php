@php
    $image = $page->image;
    $layout = $image ? $page->layout : 'plain';
    $split = in_array($layout, ['image_right', 'image_left'], true);
    $body = '[&_a]:text-industrial [&_a]:underline [&_blockquote]:border-l-4 [&_blockquote]:border-amber [&_blockquote]:pl-4 [&_blockquote]:italic [&_h1]:mt-8 [&_h1]:text-3xl [&_h1]:font-extrabold [&_h1]:text-navy [&_h2]:mt-8 [&_h2]:text-2xl [&_h2]:font-extrabold [&_h2]:text-navy [&_h3]:mt-6 [&_h3]:text-xl [&_h3]:font-bold [&_h3]:text-navy [&_img]:max-w-full [&_img]:rounded-control [&_ol]:mt-4 [&_ol]:list-decimal [&_ol]:pl-6 [&_p]:mt-4 [&_ul]:mt-4 [&_ul]:list-disc [&_ul]:pl-6';
@endphp
<x-layouts.public-shell :title="$page->seo_title ?: $page->title" :description="$page->seo_description" :noindex="$page->noindex || $page->status !== 'published'"
    :canonical="$page->canonical_url ?: $lr('page.show', ['page' => $page])" :image="$image ? url($image) : null" :breadcrumbs="[[__('site.home'), $lr('home')], [$page->title, $lr('page.show', ['page' => $page])]]">
    <x-page-header :title="$page->title" :eyebrow="\App\Support\Settings::get('brand', 'site_name', config('app.name'))" :subtitle="$page->subtitle ?: $page->seo_description" />

    @can('pages.manage')
        <div class="border-b border-amber/40 bg-amber/20"><div class="container-x flex items-center justify-between py-2 text-sm font-semibold">
            <span>{{ $page->status === 'published' ? '' : 'Draft (not public). ' }}You are signed in as an editor.</span>
            <a class="underline" href="{{ route('admin.pages.edit', $page) }}">{{ __('site.edit_page') }} →</a>
        </div></div>
    @endcan

    <article class="container-x py-12" data-layout="{{ $layout }}">
        @if ($layout === 'banner')
            <img src="{{ $image }}" alt="{{ $page->title }}" class="mx-auto mb-10 max-h-[30rem] w-full max-w-6xl rounded-panel object-cover shadow-card">
        @endif

        @if ($split)
            <div class="grid items-start gap-10 lg:grid-cols-2 lg:gap-14">
                <div class="text-lg leading-relaxed {{ $body }} {{ $layout === 'image_left' ? 'lg:order-2' : '' }} [&>*:first-child]:mt-0">{!! $page->body !!}</div>
                <img src="{{ $image }}" alt="{{ $page->title }}" loading="lazy" class="w-full rounded-panel object-cover shadow-lift ring-1 ring-steel/40 lg:sticky lg:top-24 {{ $layout === 'image_left' ? 'lg:order-1' : '' }}">
            </div>
        @else
            <div class="mx-auto max-w-5xl text-lg leading-relaxed {{ $body }} [&>*:first-child]:mt-0">{!! $page->body !!}</div>
        @endif
    </article>
</x-layouts.public-shell>
