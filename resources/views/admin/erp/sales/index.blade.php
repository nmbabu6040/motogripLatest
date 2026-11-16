@php use App\Support\Money; @endphp
<x-layouts.admin-shell title="Sales and invoices">
    <div class="mb-4 flex flex-wrap items-end justify-between gap-3">
        <form method="GET" class="flex flex-wrap items-end gap-2" role="search">
            <div><label class="sr-only" for="q">Search</label><input id="q" name="q" value="{{ request('q') }}" placeholder="Invoice no. or customer" class="rounded-control border border-steel px-3 py-2"></div>
            <div><label class="sr-only" for="status">Status</label><select id="status" name="status" class="rounded-control border border-steel px-3 py-2"><option value="">All</option><option value="due" @selected(request('status') === 'due')>Unpaid / part-paid</option><option value="void" @selected(request('status') === 'void')>Voided</option></select></div>
            <div><label class="block text-xs" for="from">From</label><input id="from" type="date" name="from" value="{{ request('from') }}" class="rounded-control border border-steel px-2 py-1.5"></div>
            <div><label class="block text-xs" for="to">To</label><input id="to" type="date" name="to" value="{{ request('to') }}" class="rounded-control border border-steel px-2 py-1.5"></div>
            <x-button>Filter</x-button>
        </form>
        <a href="{{ route('admin.sales.create') }}" class="rounded-control bg-navy px-5 py-2.5 font-semibold text-white hover:bg-industrial">New sale</a>
    </div>
    <div class="overflow-x-auto rounded-panel border border-steel bg-white"><table class="w-full text-left text-sm">
        <thead class="bg-surface"><tr><th class="p-3">Invoice</th><th class="p-3">Date</th><th class="p-3">Customer</th><th class="p-3 text-right">Total</th><th class="p-3 text-right">Due</th><th class="p-3">Status</th><th class="p-3">Actions</th></tr></thead>
        <tbody>
        @forelse ($sales as $s)
            @php $due = max(0, round((float) $s->total - (float) $s->returns_sum_refund_total - (float) $s->payments_sum_amount, 2)); @endphp
            <tr class="border-t border-steel">
                <td class="p-3"><a class="font-semibold text-industrial underline" href="{{ route('admin.sales.show', $s) }}">{{ $s->number() }}</a></td>
                <td class="p-3 whitespace-nowrap">{{ $s->sold_on->format('Y-m-d') }}</td><td class="p-3">{{ $s->customerLabel() }}</td>
                <td class="p-3 text-right">{{ Money::format($s->total) }}</td>
                <td class="p-3 text-right {{ $due > 0 && ! $s->isVoid() ? 'font-bold text-red-700' : '' }}">{{ $s->isVoid() ? '—' : Money::format($due) }}</td>
                <td class="p-3">{!! $s->isVoid() ? '<span class="rounded-full bg-red-100 px-2 py-0.5 text-xs font-bold text-red-800">Void</span>' : ($due > 0 ? '<span class="rounded-full bg-amber/30 px-2 py-0.5 text-xs font-bold">Due</span>' : '<span class="rounded-full bg-green-100 px-2 py-0.5 text-xs font-bold text-green-900">Paid</span>') !!}</td>
                <td class="whitespace-nowrap p-3">
                    <a class="underline text-industrial" href="{{ route('admin.sales.show', $s) }}">View<span class="sr-only"> {{ $s->number() }}</span></a>
                    @unless ($s->isVoid())
                        <span aria-hidden="true"> · </span><a class="underline text-industrial" href="{{ route('admin.sales.edit', $s) }}">Edit<span class="sr-only"> {{ $s->number() }}</span></a>
                        <span aria-hidden="true"> · </span><a class="underline text-industrial" href="{{ route('admin.sales.returns.create', $s) }}">Return<span class="sr-only"> items of {{ $s->number() }}</span></a>
                        <span aria-hidden="true"> · </span><a class="underline text-industrial" href="{{ route('admin.sales.challans.create', $s) }}">Challan<span class="sr-only"> for {{ $s->number() }}</span></a>
                    @endunless
                </td>
            </tr>
        @empty
            <tr><td colspan="7" class="p-8 text-center">কোনো বিক্রি নেই। "New sale" চেপে প্রথম ইনভয়েস বানান।</td></tr>
        @endforelse
        </tbody></table></div>
    <div class="mt-4">{{ $sales->links() }}</div>
</x-layouts.admin-shell>
