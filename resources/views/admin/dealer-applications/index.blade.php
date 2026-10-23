<x-layouts.admin-shell title="Dealer applications">
    <div class="mb-4 flex flex-wrap gap-3 justify-between">
        <form method="GET" class="flex gap-2"><label class="sr-only" for="status">Status</label><select id="status" name="status" class="rounded-control border border-steel px-3 py-2"><option value="">All statuses</option>@foreach (\App\Models\DealerApplication::STATUSES as $s)<option value="{{ $s }}" @selected(request('status') === $s)>{{ ucfirst(str_replace('_', ' ', $s)) }}</option>@endforeach</select>
            <label class="sr-only" for="district">District</label><input id="district" name="district" value="{{ request('district') }}" placeholder="District" class="rounded-control border border-steel px-3 py-2"><x-button>Filter</x-button></form>
        @can('inquiries.export')<a href="{{ route('admin.dealer-applications.export') }}" class="rounded-control border border-navy px-4 py-2 font-semibold text-navy hover:bg-white">Export CSV</a>@endcan
    </div>
    <div class="overflow-x-auto rounded-panel border border-steel bg-white"><table class="w-full text-left text-sm">
        <thead class="bg-surface"><tr><th class="p-3">Received</th><th class="p-3">Business</th><th class="p-3">District</th><th class="p-3">Status</th><th class="p-3">Assigned</th><th class="p-3"><span class="sr-only">Open</span></th></tr></thead>
        <tbody>@forelse ($applications as $a)<tr class="border-t border-steel"><td class="p-3 whitespace-nowrap">{{ $a->created_at->format('Y-m-d') }}</td><td class="p-3">{{ $a->business_name }}<br><span class="text-xs">{{ $a->contact_name }}</span></td><td class="p-3">{{ $a->district }}</td><td class="p-3">{{ ucfirst(str_replace('_', ' ', $a->status)) }}</td><td class="p-3">{{ $a->assignee?->name ?? '—' }}</td><td class="p-3"><a class="underline text-industrial" href="{{ route('admin.dealer-applications.show', $a) }}">Open<span class="sr-only"> {{ $a->business_name }}</span></a></td></tr>
        @empty<tr><td colspan="6" class="p-6 text-center">No applications yet.</td></tr>@endforelse</tbody></table></div>
    <div class="mt-4">{{ $applications->links() }}</div>
</x-layouts.admin-shell>
