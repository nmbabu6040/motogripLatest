<x-layouts.admin-shell title="Dealers">
    <a href="{{ route('admin.dealers.create') }}"
        class="mb-4 inline-flex rounded-control bg-navy px-5 py-2.5 font-semibold text-white hover:bg-industrial">Add
        dealer</a>
    <div class="overflow-x-auto rounded-panel border border-steel bg-white">
        <table class="w-full text-left text-sm">
            <thead class="bg-surface">
                <tr>
                    <th class="p-3">Name</th>
                    <th class="p-3">District</th>
                    <th class="p-3">Public</th>
                    <th class="p-3"><span class="sr-only">Actions</span></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($dealers as $d)
                    <tr class="border-t border-steel">
                        <td class="p-3">{{ $d->name }}</td>
                        <td class="p-3">{{ $d->district }}</td>
                        <td class="p-3">{{ $d->is_published ? 'Yes' : 'No' }}</td>
                        <td class="p-3 flex gap-3"><a class="underline text-industrial"
                                href="{{ route('admin.dealers.edit', $d) }}">Edit<span class="sr-only">
                                    {{ $d->name }}</span></a>
                            <form method="POST" action="{{ route('admin.dealers.destroy', $d) }}"
                                x-on:submit="if (!confirm('Remove this dealer?')) $event.preventDefault()">@csrf
                                @method('DELETE')<button class="text-red-800 underline">Remove<span class="sr-only">
                                        {{ $d->name }}</span></button></form>
                        </td>
                    </tr>
                @empty<tr>
                        <td colspan="4" class="p-6 text-center">No dealers yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $dealers->links() }}</div>
</x-layouts.admin-shell>
