@php $input = 'w-full rounded-control border border-steel px-3 py-2'; $label = 'mb-1 block text-sm font-semibold'; @endphp
<x-layouts.admin-shell title="{{ $link->exists ? 'Edit link' : 'Add link' }}">
    <form method="POST" enctype="multipart/form-data" action="{{ $link->exists ? route('admin.partner-links.update', $link) : route('admin.partner-links.store') }}" class="grid max-w-4xl gap-4 rounded-panel border border-steel bg-white p-6 sm:grid-cols-2">
        @csrf @if ($link->exists) @method('PUT') @endif
        <div><label class="{{ $label }}" for="store">Store</label><select id="store" name="store" class="{{ $input }}">@foreach (\App\Models\PartnerLink::STORES as $k => $v)<option value="{{ $k }}" @selected(old('store', $link->store) === $k)>{{ $v }}</option>@endforeach</select></div>
        <div><label class="{{ $label }}" for="sort_order">Order (small number shows first)</label><input id="sort_order" type="number" min="0" name="sort_order" value="{{ old('sort_order', $link->sort_order ?? 0) }}" class="{{ $input }}"></div>
        <div class="sm:col-span-2"><label class="{{ $label }}" for="url">Your affiliate link (starts with https://)</label><input id="url" name="url" type="url" required value="{{ old('url', $link->url) }}" placeholder="https://amzn.to/…" class="{{ $input }}"></div>
        <div><label class="{{ $label }}" for="t_en">Title (English)</label><input id="t_en" name="title[en]" required value="{{ old('title.en', $link->getTranslation('title', 'en', false)) }}" class="{{ $input }}"></div>
        <div><label class="{{ $label }}" for="t_bn">Title (বাংলা, optional)</label><input id="t_bn" name="title[bn]" value="{{ old('title.bn', $link->getTranslation('title', 'bn', false)) }}" class="{{ $input }}"></div>
        <div><label class="{{ $label }}" for="d_en">Short description (English)</label><textarea id="d_en" name="description[en]" rows="3" maxlength="500" class="{{ $input }}">{{ old('description.en', $link->getTranslation('description', 'en', false)) }}</textarea></div>
        <div><label class="{{ $label }}" for="d_bn">Short description (বাংলা)</label><textarea id="d_bn" name="description[bn]" rows="3" maxlength="500" class="{{ $input }}">{{ old('description.bn', $link->getTranslation('description', 'bn', false)) }}</textarea></div>
        <div><label class="{{ $label }}" for="image">Image (JPG, PNG or WebP, up to 3 MB)</label>
            @if ($link->image)<img src="{{ $link->image }}" alt="" class="mb-2 h-24 w-auto rounded-control border border-steel"><label class="mb-2 flex items-center gap-2 text-sm"><input type="checkbox" name="remove_image" value="1"> Remove image</label>@endif
            <input id="image" type="file" name="image" accept="image/jpeg,image/png,image/webp"></div>
        <label class="flex items-center gap-2 self-end"><input type="checkbox" name="is_published" value="1" @checked(old('is_published', $link->is_published))> Show on the website</label>
        <div class="sm:col-span-2"><x-button>Save link</x-button> <a class="ml-3 underline text-industrial" href="{{ route('admin.partner-links.index') }}">Cancel</a></div>
    </form>
</x-layouts.admin-shell>
