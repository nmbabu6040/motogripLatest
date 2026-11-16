@php use App\Support\Money; $input = 'rounded-control border border-steel px-3 py-2'; @endphp
<x-layouts.admin-shell title="Delivery challans">
    <section class="mb-6 max-w-4xl rounded-panel border border-steel bg-white p-5">
        <h2 class="font-extrabold text-navy">Make a new challan</h2>
        <p class="mt-1 text-sm">যে ইনভয়েসের মাল পাঠাচ্ছেন সেটা বেছে নিন। চালান আংশিক ডেলিভারির জন্যও বানানো যায়।</p>
        @if ($choices->isEmpty())
            <p class="mt-3 rounded-control bg-surface p-3 text-sm">চালান বানানোর মতো কোনো ইনভয়েস নেই।</p>
        @else
            <form method="GET" action="{{ route('admin.challans.start') }}" class="mt-3 flex flex-wrap items-end gap-3">
                <div><label class="mb-1 block text-sm font-semibold" for="sale">Invoice</label>
                    <select id="sale" name="sale" required class="min-w-72 {{ $input }}"><option value="">Choose…</option>
                        @foreach ($choices as $c)<option value="{{ $c->id }}">{{ $c->number() }} · {{ $c->created_at->format('d M') }} · {{ $c->customerLabel() }} · {{ Money::format($c->total) }}</option>@endforeach</select></div>
                <x-button>Next: choose items</x-button>
            </form>
            @error('sale')<p role="alert" class="mt-2 text-sm text-red-800">{{ $message }}</p>@enderror
        @endif
    </section>

    <div class="overflow-x-auto rounded-panel border border-steel bg-white"><table class="w-full text-left text-sm">
        <thead class="bg-surface"><tr><th class="p-3">Challan</th><th class="p-3">Date</th><th class="p-3">Invoice</th><th class="p-3">Customer</th><th class="p-3">Vehicle</th></tr></thead>
        <tbody>@forelse ($challans as $c)<tr class="border-t border-steel"><td class="p-3"><a class="font-semibold text-industrial underline" href="{{ route('admin.challans.show', $c) }}">{{ $c->number() }}</a></td><td class="p-3 whitespace-nowrap">{{ $c->delivered_on->format('Y-m-d') }}</td><td class="p-3">{{ $c->sale?->number() }}</td><td class="p-3">{{ $c->receiver_name ?: $c->sale?->customerLabel() }}</td><td class="p-3">{{ $c->vehicle_no }}</td></tr>
        @empty<tr><td colspan="5" class="p-8 text-center">No challans yet.</td></tr>@endforelse</tbody></table></div>
    <div class="mt-4">{{ $challans->links() }}</div>
</x-layouts.admin-shell>
