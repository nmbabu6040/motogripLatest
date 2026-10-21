<x-layouts.admin-shell title="Inquiries">
    <div class="mb-4 flex flex-wrap gap-3 justify-between">
        <form method="GET" class="flex flex-wrap gap-2" role="search">
            <label class="sr-only" for="q">Search</label>
            <input id="q" name="q" value="{{ request('q') }}" placeholder="Name, email, company"
                class="rounded-control border border-steel px-3 py-2">
            <label class="sr-only" for="status">Status</label>
            <select id="status" name="status" class="rounded-control border border-steel px-3 py-2">
                <option value="">All statuses</option>
                @foreach (\App\Models\Inquiry::STATUSES as $s)
                    <option value="{{ $s }}" @selected(request('status') === $s)>
                        {{ ucfirst(str_replace('_', ' ', $s)) }}</option>
                @endforeach
            </select>
            <label class="sr-only" for="type">Type</label>
            <select id="type" name="type" class="rounded-control border border-steel px-3 py-2">
                <option value="">All types</option>
                @foreach ($types as $k => $v)
                    <option value="{{ $k }}" @selected(request('type') === $k)>{{ $v }}</option>
                @endforeach
            </select>
            <x-button>Filter</x-button>
        </form>
        @can('inquiries.export')
            <a href="{{ route('admin.inquiries.export') }}"
                class="rounded-control border border-navy px-4 py-2 font-semibold text-navy hover:bg-white">Export CSV</a>
        @endcan
    </div>
    <div class="overflow-x-auto rounded-panel border border-steel bg-white">
        <table class="w-full text-left text-sm">
            <thead class="bg-surface">
                <tr>
                    <th class="p-3">Received</th>
                    <th class="p-3">From</th>
                    <th class="p-3">Type</th>
                    <th class="p-3">Status</th>
                    <th class="p-3">Assigned</th>
                    <th class="p-3"><span class="sr-only">Open</span></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($inquiries as $i)
                    <tr class="border-t border-steel">
                        <td class="p-3 whitespace-nowrap">{{ $i->created_at->format('Y-m-d H:i') }}</td>
                        <td class="p-3">{{ $i->name }}<br><span class="text-xs">{{ $i->email }}</span></td>
                        <td class="p-3">{{ \App\Models\Inquiry::TYPES[$i->type] ?? $i->type }}</td>
                        <td class="p-3">{{ ucfirst(str_replace('_', ' ', $i->status)) }}</td>
                        <td class="p-3">{{ $i->assignee?->name ?? '—' }}</td>
                        <td class="p-3"><a class="underline text-industrial"
                                href="{{ route('admin.inquiries.show', $i) }}">Open<span class="sr-only"> inquiry from
                                    {{ $i->name }}</span></a></td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="p-6 text-center">No inquiries yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $inquiries->links() }}</div>
</x-layouts.admin-shell>
