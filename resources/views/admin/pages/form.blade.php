@php $input = 'w-full rounded-control border border-steel px-3 py-2'; $label = 'mb-1 block text-sm font-semibold'; @endphp
<x-layouts.admin-shell title="{{ $page->exists ? 'Edit page' : 'Add page' }}">
    {{-- The file-attachment button is hidden: photos are added with the Image option below. --}}
    <style>trix-toolbar .trix-button-group--file-tools { display: none; }</style>

    <form method="POST" enctype="multipart/form-data" action="{{ $page->exists ? route('admin.pages.update', $page) : route('admin.pages.store') }}" class="max-w-4xl space-y-6">
        @csrf @if ($page->exists) @method('PUT') @endif

        <section class="grid gap-4 rounded-panel border border-steel bg-white p-6 sm:grid-cols-2">
            <h2 class="sm:col-span-2 text-lg font-extrabold">1. Page</h2>
            <div><label class="{{ $label }}" for="t_en">Title (English)</label><input id="t_en" name="title[en]" required value="{{ old('title.en', $page->getTranslation('title', 'en', false)) }}" class="{{ $input }}"></div>
            <div><label class="{{ $label }}" for="t_bn">Title (বাংলা, optional)</label><input id="t_bn" name="title[bn]" value="{{ old('title.bn', $page->getTranslation('title', 'bn', false)) }}" class="{{ $input }}"></div>
            <div><label class="{{ $label }}" for="sub_en">Short intro under the title (English)</label><input id="sub_en" name="subtitle[en]" maxlength="200" value="{{ old('subtitle.en', $page->getTranslation('subtitle', 'en', false)) }}" class="{{ $input }}"></div>
            <div><label class="{{ $label }}" for="sub_bn">Short intro (বাংলা, optional)</label><input id="sub_bn" name="subtitle[bn]" maxlength="200" value="{{ old('subtitle.bn', $page->getTranslation('subtitle', 'bn', false)) }}" class="{{ $input }}"></div>
            <div><label class="{{ $label }}" for="slug">Web address (slug)</label><input id="slug" name="slug" placeholder="about-us" value="{{ old('slug', $page->slug) }}" class="{{ $input }}"><p class="mt-1 text-xs text-ink/70">যেমন <code>about-us</code> হলে ঠিকানা হবে /about-us। ফাঁকা রাখলে শিরোনাম থেকে নিজে বানাবে।</p></div>
            <div><label class="{{ $label }}" for="status">Status</label><select id="status" name="status" class="{{ $input }}"><option value="published" @selected(old('status', $page->status) === 'published')>Published (visible)</option><option value="draft" @selected(old('status', $page->status) === 'draft')>Draft (hidden)</option></select></div>
            <div class="sm:col-span-2"><label class="{{ $label }}" for="sd_en">Short line under the heading (English, optional)</label><input id="sd_en" name="seo_description[en]" maxlength="170" value="{{ old('seo_description.en', $page->getTranslation('seo_description', 'en', false)) }}" class="{{ $input }}"><p class="mt-1 text-xs text-ink/70">হেডিংয়ের নিচে ছোট একটি লাইন হিসেবে দেখায় এবং গুগল সার্চেও কাজে লাগে।</p></div>
            <div class="sm:col-span-2"><label class="{{ $label }}" for="sd_bn">Short line (বাংলা, optional)</label><input id="sd_bn" name="seo_description[bn]" maxlength="170" value="{{ old('seo_description.bn', $page->getTranslation('seo_description', 'bn', false)) }}" class="{{ $input }}"></div>
            <label class="flex items-center gap-2 sm:col-span-2"><input type="checkbox" name="show_in_menu" value="1" @checked(old('show_in_menu', $page->show_in_menu))> <span>Show in the main menu <span class="text-xs text-ink/70">(মেনুতে দেখান)</span></span></label>
        </section>

        <section class="rounded-panel border border-steel bg-white p-6">
            <h2 class="text-lg font-extrabold">2. Image and layout</h2>
            <div class="mt-4 grid gap-4 sm:grid-cols-2">
                <div><label class="{{ $label }}" for="layout">Layout</label>
                    <select id="layout" name="layout" class="{{ $input }}">@foreach (\App\Models\Page::LAYOUTS as $k => $v)<option value="{{ $k }}" @selected(old('layout', $page->layout) === $k)>{{ $v }}</option>@endforeach</select>
                    <p class="mt-1 text-xs text-ink/70">ছবি না দিলে লেআউট নিজে "Text only" হয়ে যায়।</p></div>
                <div><label class="{{ $label }}" for="image">Image (JPG, PNG or WebP, up to 4 MB)</label>
                    @if ($page->image)<img src="{{ $page->image }}" alt="" class="mb-2 h-24 w-auto rounded-control border border-steel"><label class="mb-2 flex items-center gap-2 text-sm"><input type="checkbox" name="remove_image" value="1"> Remove this image</label>@endif
                    <input id="image" type="file" name="image" accept="image/jpeg,image/png,image/webp"></div>
            </div>
        </section>

        <section class="space-y-5 rounded-panel border border-steel bg-white p-6">
            <h2 class="text-lg font-extrabold">3. Content</h2>
            @foreach (['en' => 'English', 'bn' => 'বাংলা (optional)'] as $l => $ln)
                <div><span class="{{ $label }}" id="lbl_{{ $l }}">Text ({{ $ln }})</span>
                    <input id="body_{{ $l }}" type="hidden" name="body[{{ $l }}]" value="{{ old("body.$l", $page->getTranslation('body', $l, false)) }}">
                    <trix-editor input="body_{{ $l }}" aria-labelledby="lbl_{{ $l }}" class="trix-content min-h-56 rounded-control border border-steel bg-white"></trix-editor></div>
            @endforeach
        </section>

        <details class="rounded-panel border border-steel bg-white p-6">
            <summary class="cursor-pointer font-extrabold">4. Search engine details (optional)</summary>
            <div class="mt-4 grid gap-4 sm:grid-cols-2">
                <div><label class="{{ $label }}" for="seo_t">SEO title (max 70)</label><input id="seo_t" name="seo_title[en]" value="{{ old('seo_title.en', $page->getTranslation('seo_title', 'en', false)) }}" class="{{ $input }}"></div>
                <label class="flex items-center gap-2"><input type="checkbox" name="noindex" value="1" @checked(old('noindex', $page->noindex))> Hide from search engines</label>
            </div>
        </details>

        <div><x-button>Save page</x-button> <a class="ml-3 underline text-industrial" href="{{ route('admin.pages.index') }}">Cancel</a></div>
    </form>
</x-layouts.admin-shell>
