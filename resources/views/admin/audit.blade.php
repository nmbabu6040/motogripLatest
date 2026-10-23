<x-layouts.admin-shell title="Audit log">
    <div class="overflow-x-auto rounded-panel border border-steel bg-white">
        <table class="w-full text-left text-sm">
            <thead class="bg-surface"><tr><th class="p-3">When</th><th class="p-3">Who</th><th class="p-3">Action</th><th class="p-3">Details</th></tr></thead>
            <tbody>
            @forelse ($entries as $entry)
                <tr class="border-t border-steel align-top">
                    <td class="p-3 whitespace-nowrap">{{ $entry->created_at->format('Y-m-d H:i') }}</td>
                    <td class="p-3">{{ $entry->causer?->name ?? 'System' }}</td>
                    <td class="p-3">{{ $entry->description }}</td>
                    <td class="p-3 break-all">{{ \Illuminate\Support\Str::limit(json_encode($entry->properties), 200) }}</td>
                </tr>
            @empty
                <tr><td colspan="4" class="p-6 text-center">No activity recorded yet.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $entries->links() }}</div>
</x-layouts.admin-shell>
