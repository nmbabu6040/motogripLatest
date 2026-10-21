<x-layouts.admin-shell title="Pages">
    <a href="{{ route('admin.pages.create') }}"
        class="mb-4 inline-flex rounded-control bg-navy px-5 py-2.5 font-semibold text-white hover:bg-industrial">Add
        page</a>
    <div class="overflow-x-auto rounded-panel border border-steel bg-white">
        <table class="w-full text-left text-sm">
            <thead class="bg-surface">
                <tr>
                    <th class="p-3">Title</th>
                    <th class="p-3">URL</th>
                    <th class="p-3">Status</th>
                    <th class="p-3"><span class="sr-only">Actions</span></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($pages as $p)
                    <tr class="border-t border-steel">
                        <td class="p-3">{{ $p->title }}</td>
                        <td class="p-3">/{{ $p->slug }}</td>
                        <td class="p-3">{{ ucfirst($p->status) }}</td>
                        <td class="p-3 flex gap-3"><a class="underline text-industrial"
                                href="{{ route('admin.pages.edit', $p) }}">Edit<span class="sr-only">
                                    {{ $p->title }}</span></a>
                            <form method="POST" action="{{ route('admin.pages.destroy', $p) }}"
                                x-on:submit="if (!confirm('Delete this page?')) $event.preventDefault()">
                                @csrf
                                @method('DELETE')
                                <button class="text-red-800 underline">Delete<span class="sr-only">
                                        {{ $p->title }}</span></button>
                            </form>
                        </td>
                    </tr>
                @empty<tr>
                        <td colspan="4" class="p-6 text-center">No pages yet. Add your privacy policy and about page
                            here.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-layouts.admin-shell>
