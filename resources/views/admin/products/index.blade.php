<x-layouts.admin-shell title="Products">
    <div class="mb-4 flex flex-wrap gap-3 items-center justify-between">
        <form method="GET" class="flex gap-2" role="search">
            <label for="q" class="sr-only">Search</label>
            <input id="q" name="q" value="{{ request('q') }}" placeholder="Name or SKU"
                class="rounded-control border border-steel px-3 py-2">
            <label for="status" class="sr-only">Status</label>
            <select id="status" name="status" class="rounded-control border border-steel px-3 py-2">
                <option value="">All statuses</option>
                @foreach (['draft', 'published', 'archived'] as $s)
                    <option value="{{ $s }}" @selected(request('status') === $s)>{{ ucfirst($s) }}</option>
                @endforeach
            </select>
            <x-button>Filter</x-button>
        </form>
        @can('create', App\Models\Product::class)
            <a href="{{ route('admin.products.create') }}"
                class="rounded-control bg-navy px-5 py-2.5 font-semibold text-white hover:bg-industrial">Add product</a>
        @endcan
    </div>
    <div class="overflow-x-auto rounded-panel border border-steel bg-white">
        <table class="w-full text-left text-sm">
            <thead class="bg-surface">
                <tr>
                    <th class="p-3">SKU</th>
                    <th class="p-3">Name</th>
                    <th class="p-3">Category</th>
                    <th class="p-3">Status</th>
                    <th class="p-3"><span class="sr-only">Actions</span></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($products as $product)
                    <tr class="border-t border-steel">
                        <td class="p-3">{{ $product->sku }}</td>
                        <td class="p-3">{{ $product->name }}</td>
                        <td class="p-3">{{ $product->category?->name }}</td>
                        <td class="p-3"><span
                                class="rounded-full px-2 py-0.5 text-xs font-semibold {{ $product->status === 'published' ? 'bg-green-100 text-green-900' : 'bg-amber/30 text-ink' }}">{{ $product->status === 'published' ? 'Visible' : ucfirst($product->status) }}</span>
                        </td>
                        <td class="p-3 flex gap-3">
                            <a class="text-industrial underline" target="_blank" rel="noopener"
                                href="{{ route('catalog.show', $product) }}">View<span class="sr-only">
                                    {{ $product->sku }} on site</span></a>
                            @can('update', $product)
                                <a class="text-industrial underline"
                                    href="{{ route('admin.products.edit', $product) }}">Edit<span class="sr-only">
                                        {{ $product->sku }}</span></a>
                            @endcan
                            @can('delete', $product)
                                <form method="POST" action="{{ route('admin.products.destroy', $product) }}"
                                    x-on:submit="if (!confirm('Move this product to trash?')) $event.preventDefault()">
                                    @csrf
                                    @method('DELETE')
                                    <button class="text-red-800 underline">Delete<span class="sr-only">
                                            {{ $product->sku }}</span></button>
                                </form>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="p-6 text-center">No products yet. Add the first one to start the
                            catalog.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $products->links() }}</div>
</x-layouts.admin-shell>
