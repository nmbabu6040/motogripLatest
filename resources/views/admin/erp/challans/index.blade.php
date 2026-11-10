<x-layouts.admin-shell title="Delivery challans">
    <p class="mb-4 text-sm">নতুন চালান বানাতে ইনভয়েসের পাতা খুলে "Create challan" চাপুন। চালান আংশিক ডেলিভারির জন্যও বানানো যায়।</p>
    <div class="overflow-x-auto rounded-panel border border-steel bg-white"><table class="w-full text-left text-sm">
        <thead class="bg-surface"><tr><th class="p-3">Challan</th><th class="p-3">Date</th><th class="p-3">Invoice</th><th class="p-3">Customer</th><th class="p-3">Vehicle</th></tr></thead>
        <tbody>@forelse ($challans as $c)<tr class="border-t border-steel"><td class="p-3"><a class="font-semibold text-industrial underline" href="{{ route('admin.challans.show', $c) }}">{{ $c->number() }}</a></td><td class="p-3 whitespace-nowrap">{{ $c->delivered_on->format('Y-m-d') }}</td><td class="p-3">{{ $c->sale?->number() }}</td><td class="p-3">{{ $c->receiver_name ?: $c->sale?->customerLabel() }}</td><td class="p-3">{{ $c->vehicle_no }}</td></tr>
        @empty<tr><td colspan="5" class="p-8 text-center">No challans yet.</td></tr>@endforelse</tbody></table></div>
    <div class="mt-4">{{ $challans->links() }}</div>
</x-layouts.admin-shell>
