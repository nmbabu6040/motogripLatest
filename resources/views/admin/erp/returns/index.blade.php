@php use App\Support\Money; $isSale = $kind === 'sale'; @endphp
<x-layouts.admin-shell title="{{ $isSale ? 'Sale returns' : 'Purchase returns' }}">
    <p class="mb-4 text-sm">নতুন রিটার্ন বানাতে {{ $isSale ? 'ইনভয়েস' : 'ক্রয়ের' }} পাতা খুলে "{{ $isSale ? 'Return items' : 'Return items to supplier' }}" চাপুন।</p>
    <div class="overflow-x-auto rounded-panel border border-steel bg-white"><table class="w-full text-left text-sm">
        <thead class="bg-surface"><tr><th class="p-3">Return</th><th class="p-3">Date</th><th class="p-3">{{ $isSale ? 'Invoice' : 'Purchase' }}</th><th class="p-3">{{ $isSale ? 'Customer' : 'Supplier' }}</th><th class="p-3 text-right">{{ $isSale ? 'Refund' : 'Credit' }}</th></tr></thead>
        <tbody>@forelse ($returns as $r)
            @php $doc = $isSale ? $r->sale : $r->purchase; @endphp
            <tr class="border-t border-steel"><td class="p-3"><a class="font-semibold text-industrial underline" href="{{ route($isSale ? 'admin.sale-returns.show' : 'admin.purchase-returns.show', $r) }}">{{ $r->number() }}</a></td>
                <td class="p-3 whitespace-nowrap">{{ $r->returned_on->format('Y-m-d') }}</td><td class="p-3">{{ $doc?->number() }}</td>
                <td class="p-3">{{ $isSale ? $doc?->customerLabel() : $doc?->supplier?->name }}</td><td class="p-3 text-right">{{ Money::format($isSale ? $r->refund_total : $r->credit_total) }}</td></tr>
        @empty<tr><td colspan="5" class="p-8 text-center">No returns yet.</td></tr>@endforelse</tbody></table></div>
    <div class="mt-4">{{ $returns->links() }}</div>
</x-layouts.admin-shell>
