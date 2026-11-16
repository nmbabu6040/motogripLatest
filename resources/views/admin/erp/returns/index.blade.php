@php use App\Support\Money; $isSale = $kind === 'sale'; $input = 'rounded-control border border-steel px-3 py-2'; @endphp
<x-layouts.admin-shell title="{{ $isSale ? 'Sale returns' : 'Purchase returns' }}">
    <section class="mb-6 max-w-4xl rounded-panel border border-steel bg-white p-5">
        <h2 class="font-extrabold text-navy">{{ $isSale ? 'Make a new sale return' : 'Make a new purchase return' }}</h2>
        <p class="mt-1 text-sm">{{ $isSale ? 'যে ইনভয়েসের পণ্য ফেরত এসেছে সেটা বেছে নিন, তারপর কোন পণ্য কত ফেরত তা লিখুন।' : 'যে ক্রয়ের পণ্য সাপ্লায়ারকে ফেরত পাঠাচ্ছেন সেটা বেছে নিন, তারপর কোন পণ্য কত ফেরত তা লিখুন।' }}</p>
        @if ($choices->isEmpty())
            <p class="mt-3 rounded-control bg-surface p-3 text-sm">{{ $isSale ? 'ফেরত নেওয়ার মতো কোনো ইনভয়েস নেই।' : 'ফেরত পাঠানোর মতো কোনো ক্রয় নেই।' }}</p>
        @else
            <form method="GET" action="{{ route($isSale ? 'admin.sale-returns.start' : 'admin.purchase-returns.start') }}" class="mt-3 flex flex-wrap items-end gap-3">
                <div><label class="mb-1 block text-sm font-semibold" for="doc">{{ $isSale ? 'Invoice' : 'Purchase' }}</label>
                    <select id="doc" name="{{ $isSale ? 'sale' : 'purchase' }}" required class="min-w-72 {{ $input }}"><option value="">Choose…</option>
                        @foreach ($choices as $c)<option value="{{ $c->id }}">{{ $c->number() }} · {{ $c->created_at->format('d M') }} · {{ $isSale ? $c->customerLabel() : $c->supplier?->name }} · {{ Money::format($c->total) }}</option>@endforeach</select></div>
                <x-button>Next: choose items</x-button>
            </form>
            @error($isSale ? 'sale' : 'purchase')<p role="alert" class="mt-2 text-sm text-red-800">{{ $message }}</p>@enderror
        @endif
    </section>

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
