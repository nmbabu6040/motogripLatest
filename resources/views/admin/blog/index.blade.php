<x-layouts.admin-shell title="Articles">
    <div class="mb-4 flex flex-wrap gap-3 justify-between">
        <form method="GET" class="flex gap-2" role="search">
            <label class="sr-only" for="q">Search</label>
            <input id="q" name="q" value="{{ request('q') }}" placeholder="Title"
                class="rounded-control border border-steel px-3 py-2">
            <label class="sr-only" for="status">Status</label>
            <select id="status" name="status" class="rounded-control border border-steel px-3 py-2">
                <option value="">All</option>
                @foreach (\App\Models\BlogPost::STATUSES as $s)
                    <option value="{{ $s }}" @selected(request('status') === $s)>{{ ucfirst($s) }}</option>
                @endforeach
            </select>
            <x-button>Filter</x-button>
        </form>
        <div class="flex gap-3">
            <a href="{{ route('admin.blog.taxonomy') }}"
                class="rounded-control border border-navy px-4 py-2 font-semibold text-navy hover:bg-white">Categories
                and tags</a><a href="{{ route('admin.blog.create') }}"
                class="rounded-control bg-navy px-5 py-2.5 font-semibold text-white hover:bg-industrial">Write
                article</a>
        </div>
    </div>
    <div class="overflow-x-auto rounded-panel border border-steel bg-white">
        <table class="w-full text-left text-sm">
            <thead class="bg-surface">
                <tr>
                    <th class="p-3">Title</th>
                    <th class="p-3">Author</th>
                    <th class="p-3">Status</th>
                    <th class="p-3">Published</th>
                    <th class="p-3"><span class="sr-only">Actions</span></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($posts as $p)
                    <tr class="border-t border-steel">
                        <td class="p-3">{{ $p->title }}</td>
                        <td class="p-3">{{ $p->author?->name }}</td>
                        <td class="p-3">{{ ucfirst($p->status) }}</td>
                        <td class="p-3">{{ $p->published_at?->format('Y-m-d H:i') ?? '—' }}</td>
                        <td class="p-3 flex gap-3"><a class="underline text-industrial"
                                href="{{ route('admin.blog.edit', $p) }}">Edit<span class="sr-only">
                                    {{ $p->title }}</span></a>
                            <form method="POST" action="{{ route('admin.blog.destroy', $p) }}"
                                x-on:submit="if (!confirm('Move this article to trash?')) $event.preventDefault()">@csrf
                                @method('DELETE')<button class="text-red-800 underline">Delete<span class="sr-only">
                                        {{ $p->title }}</span></button></form>
                        </td>
                    </tr>
                @empty<tr>
                        <td colspan="5" class="p-6 text-center">No articles yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $posts->links() }}</div>
</x-layouts.admin-shell>
