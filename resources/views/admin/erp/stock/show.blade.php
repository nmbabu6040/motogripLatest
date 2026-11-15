@php use App\Support\Money; @endphp
<x-layouts.admin-shell title="Stock: {{ $item->label() }}">
    <div class="mb-6 grid max-w-4xl gap-4 sm:grid-cols-3">
        <div class="rounded-panel border border-steel bg-white p-4"><p class="text-sm">In stock</p><p class="text-3xl font-extrabold text-navy">{{ Money::qty($item->balance) }}</p></div>
        <div class="rounded-panel border border-steel bg-white p-4"><p class="text-sm">Average cost</p><p class="text-2xl font-extrabold text-navy">{{ Money::format($item->cost_price) }}</p></div>
        <div class="rounded-panel border border-steel bg-white p-4"><p class="text-sm">Sale price</p><p class="text-2xl font-extrabold text-navy">{{ Money::format($item->sale_price) }}</p></div>
    </div>

    @can('inventory.manage')
        <form method="POST" action="{{ route('admin.stock.adjust', $item) }}" class="mb-8 flex max-w-4xl flex-wrap items-end gap-3 rounded-panel border border-steel bg-white p-5">
            @csrf
            <h2 class="w-full font-extrabold">Adjust stock (count correction, damage, opening stock)</h2>
            <div><label class="mb-1 block text-sm font-semibold" for="delta">Change (+ or −)</label><input id="delta" name="delta" type="number" step="1" required class="w-32 rounded-control border border-steel px-3 py-2" placeholder="-2 or 10"></div>
            <div class="grow"><label class="mb-1 block text-sm font-semibold" for="reason">Reason</label><input id="reason" name="reason" required maxlength="200" class="w-full rounded-control border border-steel px-3 py-2" placeholder="e.g. Opening stock, damaged in transport"></div>
            <div><label class="mb-1 block text-sm font-semibold" for="moved_on">Date</label><input id="moved_on" name="moved_on" type="date" value="{{ today()->toDateString() }}" class="rounded-control border border-steel px-3 py-2"></div>
            <x-button>Apply</x-button>
        </form>
    @endcan

    <h2 class="mb-2 text-lg font-extrabold">History</h2>
    <div class="max-w-4xl overflow-x-auto rounded-panel border border-steel bg-white"><table class="w-full text-left text-sm">
        <thead class="bg-surface"><tr><th class="p-3">Date</th><th class="p-3">Type</th><th class="p-3 text-right">Change</th><th class="p-3">Note</th><th class="p-3">By</th></tr></thead>
        <tbody>@forelse ($movements as $m)<tr class="border-t border-steel"><td class="p-3 whitespace-nowrap">{{ $m->moved_on->format('Y-m-d') }}</td><td class="p-3">{{ \App\Models\StockMovement::TYPES[$m->type] ?? $m->type }}</td>
            <td class="p-3 text-right font-bold {{ $m->quantity < 0 ? 'text-red-700' : 'text-green-700' }}">{{ $m->quantity > 0 ? '+' : '' }}{{ $m->quantity }}</td><td class="p-3">{{ $m->note }}</td><td class="p-3">{{ $m->user?->name }}</td></tr>
        @empty<tr><td colspan="5" class="p-6 text-center">No movements yet.</td></tr>@endforelse</tbody></table></div>
    <div class="mt-4">{{ $movements->links() }}</div>
    <p class="mt-4 flex gap-4"><a class="underline text-industrial" href="{{ route('admin.stock.index') }}">← Back to stock</a>@can('inventory.manage')<a class="underline text-industrial" href="{{ route('admin.stock.edit', $item) }}">Edit or delete this item</a>@endcan</p>
</x-layouts.admin-shell>
