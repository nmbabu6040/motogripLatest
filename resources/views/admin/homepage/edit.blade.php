@php
    $items = collect($content['items'] ?? [])
        ->map(
            fn($i) => [
                'title_en' => $i['title']['en'] ?? '',
                'title_bn' => $i['title']['bn'] ?? '',
                'text_en' => $i['text']['en'] ?? '',
                'text_bn' => $i['text']['bn'] ?? '',
            ],
        )
        ->values();
@endphp
<x-layouts.admin-shell title="Edit section: {{ ucfirst(str_replace('_', ' ', $key)) }}">
    <form method="POST" action="{{ route('admin.homepage.update', $key) }}"
        class="max-w-3xl space-y-4 rounded-panel border border-steel bg-white p-6" x-data="{ items: @js($items) }">
        @csrf
        @method('PUT')
        @if ($key === 'statistics')
            <p class="rounded-control bg-amber/30 p-3 text-sm font-semibold">Enter only verified figures. For each item,
                "Title" is the label and "Text" is the number or value.</p>
        @endif
        <div class="grid gap-4 sm:grid-cols-2">
            <x-field name="title[en]" label="Title (English)" :value="$content['title']['en'] ?? ''" />
            <x-field name="title[bn]" label="Title (বাংলা)" :value="$content['title']['bn'] ?? ''" />
            <div>
                <label class="block text-sm font-semibold mb-1" for="b_en">Text (English)</label>
                <textarea id="b_en" name="body[en]" rows="4" class="w-full rounded-control border border-steel px-3 py-2">{{ old('body.en', $content['body']['en'] ?? '') }}</textarea>
            </div>
            <div>
                <label class="block text-sm font-semibold mb-1" for="b_bn">Text (বাংলা)</label>
                <textarea id="b_bn" name="body[bn]" rows="4" class="w-full rounded-control border border-steel px-3 py-2">{{ old('body.bn', $content['body']['bn'] ?? '') }}</textarea>
            </div>
        </div>
        <fieldset>
            <legend class="font-bold mb-2">Items (optional)</legend>
            <template x-for="(row, i) in items" :key="i">
                <div class="mb-3 grid gap-2 sm:grid-cols-2 rounded-control border border-steel p-3">
                    <input :name="`items[${i}][title_en]`" x-model="row.title_en" placeholder="Title (English)"
                        aria-label="Title English" class="rounded-control border border-steel px-3 py-2">
                    <input :name="`items[${i}][title_bn]`" x-model="row.title_bn" placeholder="Title (বাংলা)"
                        aria-label="Title Bengali" class="rounded-control border border-steel px-3 py-2">
                    <input :name="`items[${i}][text_en]`" x-model="row.text_en" placeholder="Text (English)"
                        aria-label="Text English" class="rounded-control border border-steel px-3 py-2">
                    <input :name="`items[${i}][text_bn]`" x-model="row.text_bn" placeholder="Text (বাংলা)"
                        aria-label="Text Bengali" class="rounded-control border border-steel px-3 py-2">
                    <button type="button" class="text-left text-red-800 underline"
                        x-on:click="items.splice(i, 1)">Remove</button>
                </div>
            </template>
            <button type="button" class="underline text-industrial"
                x-on:click="items.push({title_en: '', title_bn: '', text_en: '', text_bn: ''})">Add item</button>
        </fieldset>
        <div>
            <x-button>Save section</x-button> <a class="ml-3 underline text-industrial"
                href="{{ route('admin.homepage.index') }}">Cancel</a>
        </div>
    </form>
</x-layouts.admin-shell>
