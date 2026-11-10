@php use App\Support\Money; $input = 'rounded-control border border-steel px-2 py-1.5'; @endphp
<x-layouts.admin-shell title="Stock">
    <dl class="mb-6 grid max-w-5xl gap-4 sm:grid-cols-4">
        @foreach ([['Items', Money::qty($summary['items']), 'text-navy'], ['Stock value (at cost)', Money::format($summary['value']), 'text-navy'], ['Running low', $summary['low'], 'text-amber-700'], ['Out of stock', $summary['out'], 'text-red-700']] as [$l, $v, $c])
            <div class="rounded-panel border border-steel bg-white p-4"><dt class="text-sm">{{ $l }}</dt><dd class="text-2xl font-extrabold {{ $c }}">{{ $v }}</dd></div>
        @endforeach
    </dl>

    <div class="mb-4 flex flex-wrap items-end justify-between gap-3">
        <form method="GET" class="flex flex-wrap gap-2" role="search">
            <label class="sr-only" for="q">Search</label><input id="q" name="q" value="{{ request('q') }}" placeholder="Product or SKU" class="rounded-control border border-steel px-3 py-2">
            <label class="sr-only" for="status">Status</label>
            <select id="status" name="status" class="rounded-control border border-steel px-3 py-2"><option value="">All</option>@foreach (['ok' => 'In stock', 'low' => 'Running low', 'out' => 'Out of stock'] as $k => $v)<option value="{{ $k }}" @selected(request('status') === $k)>{{ $v }}</option>@endforeach</select>
            <x-button>Filter</x-button>
        </form>
        @can('inventory.manage')
            <form method="POST" action="{{ route('admin.stock.sync') }}">@csrf<button class="rounded-control border border-navy px-4 py-2 font-semibold text-navy hover:bg-white">Add products to stock list</button></form>
        @endcan
    </div>

    <div class="overflow-x-auto rounded-panel border border-steel bg-white"><table class="w-full text-left text-sm">
        <thead class="bg-surface"><tr><th class="p-3">Product</th><th class="p-3">Stock</th><th class="p-3">Reorder at</th><th class="p-3">Sale price</th><th class="p-3">Avg cost</th><th class="p-3"><span class="sr-only">Actions</span></th></tr></thead>
        <tbody>
        @forelse ($items as $i)
            @php $low = $i->reorder_level > 0 && $i->balance > 0 && $i->balance <= $i->reorder_level; $out = $i->balance <= 0; @endphp
            <tr class="border-t border-steel align-top">
                <td class="p-3"><a class="font-semibold text-industrial underline" href="{{ route('admin.stock.show', $i) }}">{{ $i->product?->name }}</a>@if ($i->pack !== '') · {{ $i->pack }}@endif<br><span class="text-xs">{{ $i->product?->sku }}</span></td>
                <td class="p-3"><span class="rounded-full px-2.5 py-1 text-xs font-bold {{ $out ? 'bg-red-100 text-red-800' : ($low ? 'bg-amber/30 text-ink' : 'bg-green-100 text-green-900') }}">{{ Money::qty($i->balance) }}{{ $out ? ' · out' : ($low ? ' · low' : '') }}</span></td>
                @can('inventory.manage')
                    <td class="p-3"><input form="f{{ $i->id }}" name="reorder_level" type="number" min="0" value="{{ $i->reorder_level }}" aria-label="Reorder level" class="w-24 {{ $input }}"></td>
                    <td class="p-3"><input form="f{{ $i->id }}" name="sale_price" type="number" min="0" step="0.01" value="{{ $i->sale_price }}" aria-label="Sale price" class="w-28 {{ $input }}"></td>
                @else
                    <td class="p-3">{{ $i->reorder_level }}</td><td class="p-3">{{ Money::format($i->sale_price) }}</td>
                @endcan
                <td class="p-3">{{ Money::format($i->cost_price) }}</td>
                <td class="p-3">@can('inventory.manage')<form id="f{{ $i->id }}" method="POST" action="{{ route('admin.stock.update', $i) }}">@csrf @method('PUT')<button class="underline text-industrial">Save</button></form>@endcan</td>
            </tr>
        @empty
            <tr><td colspan="6" class="p-8 text-center">কোনো স্টক আইটেম নেই। উপরের "Add products to stock list" চাপলে আপনার সব পণ্য ও তাদের প্যাক সাইজ এখানে আসবে।</td></tr>
        @endforelse
        </tbody></table></div>
    <div class="mt-4">{{ $items->links() }}</div>
</x-layouts.admin-shell>
