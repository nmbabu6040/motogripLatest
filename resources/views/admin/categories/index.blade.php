<x-layouts.admin-shell title="Categories">
    @can('create', App\Models\ProductCategory::class)<a href="{{ route('admin.categories.create') }}" class="mb-4 inline-flex rounded-control bg-navy px-5 py-2.5 font-semibold text-white hover:bg-industrial">Add category</a>@endcan
    <div class="overflow-x-auto rounded-panel border border-steel bg-white">
        <table class="w-full text-left text-sm">
            <thead class="bg-surface"><tr><th class="p-3">Name</th><th class="p-3">Parent</th><th class="p-3">Products</th><th class="p-3">Active</th><th class="p-3"><span class="sr-only">Actions</span></th></tr></thead>
            <tbody>
            @foreach ($categories as $c)
                <tr class="border-t border-steel"><td class="p-3">{{ $c->name }}</td><td class="p-3">{{ $c->parent?->name ?? '—' }}</td><td class="p-3">{{ $c->products_count }}</td><td class="p-3">{{ $c->is_active ? 'Yes' : 'No' }}</td>
                    <td class="p-3 flex gap-3">
                        @can('update', $c)<a class="text-industrial underline" href="{{ route('admin.categories.edit', $c) }}">Edit<span class="sr-only"> {{ $c->name }}</span></a>@endcan
                        @can('delete', $c)<form method="POST" action="{{ route('admin.categories.destroy', $c) }}" x-on:submit="if (!confirm('Delete this category?')) $event.preventDefault()">@csrf @method('DELETE')<button class="text-red-800 underline">Delete<span class="sr-only"> {{ $c->name }}</span></button></form>@endcan
                    </td></tr>
            @endforeach
            </tbody>
        </table>
    </div>
</x-layouts.admin-shell>
