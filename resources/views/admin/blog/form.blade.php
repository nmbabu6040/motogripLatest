<x-layouts.admin-shell title="Article">
    <form method="POST" enctype="multipart/form-data"
        action="{{ $post->exists ? route('admin.blog.update', $post) : route('admin.blog.store') }}"
        class="max-w-4xl space-y-6">
        @csrf
        @if ($post->exists)
            @method('PUT')
        @endif
        <section class="rounded-panel border border-steel bg-white p-6 grid gap-4 sm:grid-cols-2">
            <x-field name="title[en]" label="Title (English)" :value="$post->getTranslation('title', 'en', false)" required />
            <x-field name="title[bn]" label="Title (বাংলা)" :value="$post->getTranslation('title', 'bn', false)" />
            <x-field name="slug" label="URL slug (optional)" :value="$post->slug" />
            <div>
                <label class="block text-sm font-semibold mb-1" for="cat">Category</label><select id="cat"
                    name="blog_category_id" class="w-full rounded-control border border-steel px-3 py-2">
                    <option value="">None</option>
                    @foreach ($categories as $c)
                        <option value="{{ $c->id }}" @selected(old('blog_category_id', $post->blog_category_id) == $c->id)>{{ $c->name }}</option>
                    @endforeach
                </select>
            </div>
            <x-field name="tags" label="Tags (comma separated)" :value="$tagNames" />
            <div>
                <label class="block text-sm font-semibold mb-1" for="status">Status</label><select id="status"
                    name="status" class="w-full rounded-control border border-steel px-3 py-2">
                    @foreach (\App\Models\BlogPost::STATUSES as $s)
                        <option value="{{ $s }}" @selected(old('status', $post->status) === $s)>{{ ucfirst($s) }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-semibold mb-1" for="published_at">Publish / schedule
                    time</label><input id="published_at" type="datetime-local" name="published_at"
                    value="{{ old('published_at', $post->published_at?->format('Y-m-d\TH:i')) }}"
                    class="w-full rounded-control border border-steel px-3 py-2">
            </div>
            <div class="flex items-end gap-6">
                <label class="flex items-center gap-2"><input type="checkbox" name="is_featured" value="1"
                        @checked(old('is_featured', $post->is_featured))> Featured</label><label class="flex items-center gap-2"><input
                        type="checkbox" name="noindex" value="1" @checked(old('noindex', $post->noindex))> Hide from search
                    engines</label>
            </div>
        </section>
        <section class="rounded-panel border border-steel bg-white p-6 space-y-4">
            @foreach (['en' => 'English', 'bn' => 'বাংলা'] as $l => $ln)
                <div>
                    <label class="block text-sm font-semibold mb-1" for="ex_{{ $l }}">Excerpt
                        ({{ $ln }})
                    </label>
                    <textarea id="ex_{{ $l }}" name="excerpt[{{ $l }}]" rows="2"
                        class="w-full rounded-control border border-steel px-3 py-2">{{ old("excerpt.$l", $post->getTranslation('excerpt', $l, false)) }}</textarea>
                </div>
                <div>
                    <span class="block text-sm font-semibold mb-1" id="lbl_{{ $l }}">Body
                        ({{ $ln }})</span>
                    <input id="body_{{ $l }}" type="hidden" name="body[{{ $l }}]"
                        value="{{ old("body.$l", $post->getTranslation('body', $l, false)) }}">
                    <trix-editor input="body_{{ $l }}" aria-labelledby="lbl_{{ $l }}"
                        class="trix-content min-h-48 rounded-control border border-steel bg-white"></trix-editor>
                </div>
            @endforeach
        </section>
        <section class="rounded-panel border border-steel bg-white p-6 grid gap-4 sm:grid-cols-2">
            <x-field name="seo_title[en]" label="SEO title (English, max 70)" :value="$post->getTranslation('seo_title', 'en', false)" />
            <x-field name="seo_description[en]" label="SEO description (English, max 170)" :value="$post->getTranslation('seo_description', 'en', false)" />
            <x-field name="canonical_url" label="Canonical URL (optional)" :value="$post->canonical_url" />
            <div>
                <label class="block text-sm font-semibold mb-1" for="img">Featured image (max 5 MB)</label><input
                    id="img" type="file" name="featured_image" accept="image/jpeg,image/png,image/webp">
            </div>
            <x-field name="image_alt" label="Image alt text" />
            @if ($post->exists && $post->getFirstMedia('featured'))
                <p class="text-sm self-end">Current image: {{ $post->getFirstMedia('featured')->file_name }}</p>
            @endif
        </section>
        <div class="flex items-center gap-4">
            <x-button>Save article</x-button><a class="underline text-industrial"
                href="{{ route('admin.blog.index') }}">Back to list</a>
            @if ($post->exists)
                <a class="underline text-industrial" target="_blank" rel="noopener"
                    href="{{ route('blog.show', $post) }}">Preview</a>
            @endif
        </div>
    </form>

    @if (!empty($revisions) && $revisions->isNotEmpty())
        <section class="mt-8 max-w-4xl rounded-panel border border-steel bg-white p-6">
            <h2 class="font-extrabold">Revision history</h2>
            <ul class="mt-3 divide-y divide-steel text-sm">
                @foreach ($revisions as $r)
                    <li class="flex items-center justify-between py-2"><span>{{ $r->created_at->format('Y-m-d H:i') }}
                            · {{ $r->user?->name ?? 'Unknown' }}</span>
                        <form method="POST" action="{{ route('admin.blog.restore', [$post, $r]) }}"
                            x-on:submit="if (!confirm('Restore this revision? The current text is saved as a new revision first.')) $event.preventDefault()">
                            @csrf
                            <button class="underline text-industrial">Restore</button>
                        </form>
                    </li>
                @endforeach
            </ul>
        </section>
    @endif
</x-layouts.admin-shell>
