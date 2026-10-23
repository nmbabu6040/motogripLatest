<x-layouts.admin-shell title="Category">
    <form method="POST" action="{{ $category->exists ? route('admin.categories.update', $category) : route('admin.categories.store') }}" class="max-w-2xl rounded-panel border border-steel bg-white p-6 grid gap-4 sm:grid-cols-2">
        @csrf @if ($category->exists) @method('PUT') @endif
        <div><label class="block text-sm font-semibold mb-1" for="n_en">Name (English)</label><input id="n_en" name="name[en]" required value="{{ old('name.en', $category->getTranslation('name', 'en', false)) }}" class="w-full rounded-control border border-steel px-3 py-2"></div>
        <div><label class="block text-sm font-semibold mb-1" for="n_bn">Name (বাংলা)</label><input id="n_bn" name="name[bn]" value="{{ old('name.bn', $category->getTranslation('name', 'bn', false)) }}" class="w-full rounded-control border border-steel px-3 py-2"></div>
        <div><label class="block text-sm font-semibold mb-1" for="slug">Slug (optional)</label><input id="slug" name="slug" value="{{ old('slug', $category->slug) }}" class="w-full rounded-control border border-steel px-3 py-2"></div>
        <div><label class="block text-sm font-semibold mb-1" for="parent">Parent category</label>
            <select id="parent" name="parent_id" class="w-full rounded-control border border-steel px-3 py-2"><option value="">None (top level)</option>
                @foreach ($parents as $p)<option value="{{ $p->id }}" @selected(old('parent_id', $category->parent_id) == $p->id)>{{ $p->name }}</option>@endforeach</select></div>
        <div class="sm:col-span-2"><label class="block text-sm font-semibold mb-1" for="d_en">Description (English)</label><textarea id="d_en" name="description[en]" rows="3" class="w-full rounded-control border border-steel px-3 py-2">{{ old('description.en', $category->getTranslation('description', 'en', false)) }}</textarea></div>
        <div class="sm:col-span-2"><label class="block text-sm font-semibold mb-1" for="d_bn">Description (বাংলা)</label><textarea id="d_bn" name="description[bn]" rows="3" class="w-full rounded-control border border-steel px-3 py-2">{{ old('description.bn', $category->getTranslation('description', 'bn', false)) }}</textarea></div>
        <div><label class="block text-sm font-semibold mb-1" for="sort">Sort order</label><input id="sort" type="number" name="sort_order" value="{{ old('sort_order', $category->sort_order ?? 0) }}" class="w-full rounded-control border border-steel px-3 py-2"></div>
        <label class="flex items-center gap-2 self-end"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $category->is_active))> Active</label>
        <div class="sm:col-span-2"><x-button>Save category</x-button> <a href="{{ route('admin.categories.index') }}" class="ml-3 text-industrial underline">Cancel</a></div>
    </form>
</x-layouts.admin-shell>
