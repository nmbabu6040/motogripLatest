<x-layouts.admin-shell title="Documents">
    <div class="mb-4 flex flex-wrap gap-3 justify-between">
        <form method="GET" class="flex gap-2"><label for="type" class="sr-only">Type</label>
            <select id="type" name="type" class="rounded-control border border-steel px-3 py-2"><option value="">All types</option>@foreach (\App\Models\Document::TYPES as $k => $v)<option value="{{ $k }}" @selected(request('type') === $k)>{{ $v }}</option>@endforeach</select>
            <x-button>Filter</x-button></form>
        @can('create', App\Models\Document::class)<a href="{{ route('admin.documents.create') }}" class="rounded-control bg-navy px-5 py-2.5 font-semibold text-white hover:bg-industrial">Upload document</a>@endcan
    </div>
    <div class="overflow-x-auto rounded-panel border border-steel bg-white">
        <table class="w-full text-left text-sm">
            <thead class="bg-surface"><tr><th class="p-3">Title</th><th class="p-3">Type</th><th class="p-3">Product</th><th class="p-3">Access</th><th class="p-3">Published</th><th class="p-3"><span class="sr-only">Actions</span></th></tr></thead>
            <tbody>
            @forelse ($documents as $d)
                <tr class="border-t border-steel align-top">
                    <td class="p-3">{{ $d->title }}</td><td class="p-3">{{ \App\Models\Document::TYPES[$d->type] ?? $d->type }}</td><td class="p-3">{{ $d->product?->sku ?? '—' }}</td>
                    <td class="p-3" colspan="2">
                        <form method="POST" action="{{ route('admin.documents.update', $d) }}" class="flex flex-wrap items-center gap-2">@csrf @method('PUT')
                            <label class="sr-only" for="v{{ $d->id }}">Access</label>
                            <select id="v{{ $d->id }}" name="visibility" class="rounded-control border border-steel px-2 py-1">@foreach (['public', 'authenticated', 'restricted'] as $v)<option value="{{ $v }}" @selected($d->visibility === $v)>{{ ucfirst($v) }}</option>@endforeach</select>
                            <label class="flex items-center gap-1"><input type="checkbox" name="published" value="1" @checked($d->published_at)> Published</label>
                            <button class="underline text-industrial">Save</button>
                        </form>
                    </td>
                    <td class="p-3"><form method="POST" action="{{ route('admin.documents.destroy', $d) }}" x-on:submit="if (!confirm('Permanently delete this document and its file?')) $event.preventDefault()">@csrf @method('DELETE')<button class="text-red-800 underline">Delete<span class="sr-only"> {{ $d->title }}</span></button></form></td>
                </tr>
            @empty
                <tr><td colspan="6" class="p-6 text-center">No documents uploaded yet.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $documents->links() }}</div>
</x-layouts.admin-shell>
