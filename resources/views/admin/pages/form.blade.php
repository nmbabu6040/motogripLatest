<x-layouts.admin-shell title="Page">
    <form method="POST" action="{{ $page->exists ? route('admin.pages.update', $page) : route('admin.pages.store') }}"
        class="max-w-3xl rounded-panel border border-steel bg-white p-6 grid gap-4">
        @csrf @if ($page->exists)
            @method('PUT')
        @endif
        <x-field name="title[en]" label="Title (English)" :value="$page->getTranslation('title', 'en', false)" required />
        <x-field name="title[bn]" label="Title (বাংলা)" :value="$page->getTranslation('title', 'bn', false)" />
        <x-field name="slug" label="URL slug (optional)" :value="$page->slug" />
        @foreach (['en' => 'English', 'bn' => 'বাংলা'] as $l => $ln)
            <div>
                <label class="block text-sm font-semibold mb-1" for="body_{{ $l }}">Content
                    ({{ $ln }}, HTML allowed, sanitised on save)
                </label>
                <textarea id="body_{{ $l }}" name="body[{{ $l }}]" rows="12"
                    class="w-full rounded-control border border-steel px-3 py-2 font-mono text-sm">{{ old("body.$l", $page->getTranslation('body', $l, false)) }}</textarea>
            </div>
        @endforeach
        <x-field name="seo_title[en]" label="SEO title (English, max 70)" :value="$page->getTranslation('seo_title', 'en', false)" />
        <x-field name="seo_description[en]" label="SEO description (English, max 170)" :value="$page->getTranslation('seo_description', 'en', false)" />
        <div class="flex flex-wrap items-center gap-6">
            <div>
                <label class="block text-sm font-semibold mb-1" for="status">Status</label>
                <select id="status" name="status" class="rounded-control border border-steel px-3 py-2">
                    <option value="draft" @selected($page->status === 'draft')>Draft</option>
                    <option value="published" @selected($page->status === 'published')>Published</option>
                </select>
            </div>
            <label class="flex items-center gap-2">
                <input type="checkbox" name="noindex" value="1" @checked(old('noindex', $page->noindex))> Hide from search
                engines</label>
        </div>
        <div>
            <x-button>Save page</x-button>
            <a class="ml-3 underline text-industrial" href="{{ route('admin.pages.index') }}">Cancel</a>
        </div>
    </form>
</x-layouts.admin-shell>
