@php use App\Support\Money; $input = 'w-full rounded-control border border-steel px-3 py-2'; @endphp
<x-layouts.admin-shell title="Edit purchase {{ $purchase->number() }}">
    @error('items')<p role="alert" class="mb-4 max-w-5xl rounded-control border border-red-300 bg-red-50 p-3 text-sm text-red-900">{{ $message }}</p>@enderror
    <form method="POST" action="{{ route('admin.purchases.update', $purchase) }}" class="max-w-5xl space-y-6">
        @csrf @method('PUT')
        <section class="grid gap-4 rounded-panel border border-steel bg-white p-6 sm:grid-cols-2">
            <div><label class="mb-1 block text-sm font-semibold" for="supplier_id">Supplier</label>
                <select id="supplier_id" name="supplier_id" required class="{{ $input }}">@foreach ($suppliers as $s)<option value="{{ $s->id }}" @selected(old('supplier_id', $purchase->supplier_id) == $s->id)>{{ $s->name }}</option>@endforeach</select></div>
            <div><label class="mb-1 block text-sm font-semibold" for="purchased_on">Date</label><input id="purchased_on" type="date" name="purchased_on" value="{{ old('purchased_on', $purchase->purchased_on->toDateString()) }}" required class="{{ $input }}"></div>
        </section>

        @if ($locked)
            <p class="rounded-control bg-amber/30 p-3 text-sm font-semibold">এই ক্রয়ে সাপ্লায়ারকে ফেরত দেওয়া আছে, তাই আইটেম বদলানো যাবে না। শুধু তারিখ, সাপ্লায়ার ও নোট বদলানো যায়।</p>
            <div class="overflow-x-auto rounded-panel border border-steel bg-white"><table class="w-full text-left text-sm"><thead class="bg-surface"><tr><th class="p-3">Item</th><th class="p-3 text-right">Qty</th><th class="p-3 text-right">Cost</th><th class="p-3 text-right">Amount</th></tr></thead>
                <tbody>@foreach ($purchase->items as $i)<tr class="border-t border-steel"><td class="p-3">{{ $i->stockItem->label() }}</td><td class="p-3 text-right">{{ $i->quantity }}</td><td class="p-3 text-right">{{ Money::format($i->unit_cost) }}</td><td class="p-3 text-right">{{ Money::format($i->line_total) }}</td></tr>@endforeach</tbody></table></div>
        @else
            @include('admin.erp._lines', ['mode' => 'purchase', 'stockItems' => $stockItems, 'oldItems' => collect(old('items') ? collect(old('items'))->map(fn ($r) => ['stock_item_id' => $r['stock_item_id'] ?? '', 'quantity' => $r['quantity'] ?? 1, 'price' => $r['unit_cost'] ?? '']) : $rows)->values()->all(), 'discount' => old('discount', $purchase->discount), 'tax' => 0])
        @endif

        <section class="rounded-panel border border-steel bg-white p-6"><label class="mb-1 block text-sm font-semibold" for="notes">Notes</label><textarea id="notes" name="notes" rows="2" class="{{ $input }}">{{ old('notes', $purchase->notes) }}</textarea></section>
        <div><x-button>Save changes</x-button> <a class="ml-3 underline text-industrial" href="{{ route('admin.purchases.show', $purchase) }}">Cancel</a></div>
    </form>
</x-layouts.admin-shell>
