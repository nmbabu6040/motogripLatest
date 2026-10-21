<x-layouts.public-shell :title="$page->seo_title ?: $page->title" :description="$page->seo_description" :noindex="$page->noindex || $page->status !== 'published'" :canonical="$page->canonical_url ?: $lr('page.show', ['page' => $page])" :breadcrumbs="[[__('site.home'), $lr('home')], [$page->title, $lr('page.show', ['page' => $page])]]">
    <x-page-header :title="$page->title" />
    <article class="container-x py-12">
        <div class="card mx-auto max-w-3xl p-6 sm:p-10">
            <div
                class="max-w-none leading-relaxed [&_a]:text-industrial [&_a]:underline [&_h2]:mt-8 [&_h2]:text-2xl [&_h2]:font-extrabold [&_h2]:text-navy [&_h3]:mt-6 [&_h3]:text-xl [&_h3]:font-bold [&_ol]:mt-4 [&_ol]:list-decimal [&_ol]:pl-6 [&_p]:mt-4 [&_ul]:mt-4 [&_ul]:list-disc [&_ul]:pl-6">
                {!! $page->body !!}</div>
        </div>
    </article>
</x-layouts.public-shell>
