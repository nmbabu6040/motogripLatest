@php use App\Support\Money; @endphp
<x-layouts.admin-shell title="Purchases">
    <div class="mb-4 flex flex-wrap items-end justify-between gap-3">
        <form method="GET" class="flex flex-wrap items-end gap-2" role="search">
            <div><label class="sr-only" for="q">Search</label><input id="q" name="q" value="{{ request('q') }}" placeholder="Purchase no. or supplier" class="rounded-control border border-steel px-3 py-2"></div>
            <div><label class="sr-only" for="status">Status</label><select id="status" name="status" class="rounded-control border border-steel px-3 py-2"><option value="">All</option><option value="due" @selected(request('status') === 'due')>We still owe</option><option value="void" @selected(request('status') === 'void')>Voided</option></select></div>
            <div><label class="block text-xs" for="from">From</label><input id="from" type="date" name="from" value="{{ request('from') }}" class="rounded-control border border-steel px-2 py-1.5"></div>
            <div><label class="block text-xs" for="to">To</label><input id="to" type="date" name="to" value="{{ request('to') }}" class="rounded-control border border-steel px-2 py-1.5"></div>
            <x-button>Filter</x-button>
        </form>
        <a href="{{ route('admin.purchases.create') }}" class="rounded-control bg-navy px-5 py-2.5 font-semibold text-white hover:bg-industrial">New purchase</a>
    </div>
    <div class="overflow-x-auto rounded-panel border border-steel bg-white"><table class="w-full text-left text-sm">
        <thead class="bg-surface"><tr><th class="p-3">Purchase</th><th class="p-3">Date</th><th class="p-3">Supplier</th><th class="p-3 text-right">Total</th><th class="p-3 text-right">We owe</th><th class="p-3">Status</th><th class="p-3">Actions</th></tr></thead>
        <tbody>
        @forelse ($purchases as $p)
            @php $due = max(0, round((float) $p->total - (float) $p->returns_sum_credit_total - (float) $p->payments_sum_amount, 2)); @endphp
            <tr class="border-t border-steel">
                <td class="p-3"><a class="font-semibold text-industrial underline" href="{{ route('admin.purchases.show', $p) }}">{{ $p->number() }}</a></td>
                <td class="p-3 whitespace-nowrap">{{ $p->purchased_on->format('Y-m-d') }}</td><td class="p-3">{{ $p->supplier?->name }}</td>
                <td class="p-3 text-right">{{ Money::format($p->total) }}</td>
                <td class="p-3 text-right {{ $due > 0 && ! $p->isVoid() ? 'font-bold text-red-700' : '' }}">{{ $p->isVoid() ? '—' : Money::format($due) }}</td>
                <td class="p-3">{!! $p->isVoid() ? '<span class="rounded-full bg-red-100 px-2 py-0.5 text-xs font-bold text-red-800">Void</span>' : ($due > 0 ? '<span class="rounded-full bg-amber/30 px-2 py-0.5 text-xs font-bold">Due</span>' : '<span class="rounded-full bg-green-100 px-2 py-0.5 text-xs font-bold text-green-900">Paid</span>') !!}</td>
                <td class="whitespace-nowrap p-3">
                    <a class="underline text-industrial" href="{{ route('admin.purchases.show', $p) }}">View<span class="sr-only"> {{ $p->number() }}</span></a>
                    @unless ($p->isVoid())
                        <span aria-hidden="true"> · </span><a class="underline text-industrial" href="{{ route('admin.purchases.edit', $p) }}">Edit<span class="sr-only"> {{ $p->number() }}</span></a>
                        <span aria-hidden="true"> · </span><a class="underline text-industrial" href="{{ route('admin.purchases.returns.create', $p) }}">Return<span class="sr-only"> items of {{ $p->number() }} to supplier</span></a>
                    @endunless
                </td>
            </tr>
        @empty
            <tr><td colspan="7" class="p-8 text-center">কোনো ক্রয় নেই। "New purchase" চেপে মাল কেনা লিখুন, স্টক নিজে বাড়বে।</td></tr>
        @endforelse
        </tbody></table></div>
    <div class="mt-4">{{ $purchases->links() }}</div>
</x-layouts.admin-shell>
