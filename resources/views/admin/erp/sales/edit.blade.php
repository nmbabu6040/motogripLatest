@php use App\Support\Money; $input = 'w-full rounded-control border border-steel px-3 py-2'; @endphp
<x-layouts.admin-shell title="Edit invoice {{ $sale->number() }}">
    @error('items')<p role="alert" class="mb-4 max-w-5xl rounded-control border border-red-300 bg-red-50 p-3 text-sm text-red-900">{{ $message }}</p>@enderror
    <form method="POST" action="{{ route('admin.sales.update', $sale) }}" class="max-w-5xl space-y-6">
        @csrf @method('PUT')
        <section class="grid gap-4 rounded-panel border border-steel bg-white p-6 sm:grid-cols-3">
            <div><label class="mb-1 block text-sm font-semibold" for="customer_id">Customer</label>
                <select id="customer_id" name="customer_id" class="{{ $input }}"><option value="">Walk-in customer</option>@foreach ($customers as $c)<option value="{{ $c->id }}" @selected(old('customer_id', $sale->customer_id) == $c->id)>{{ $c->name }}</option>@endforeach</select></div>
            <div><label class="mb-1 block text-sm font-semibold" for="customer_name">Walk-in name (optional)</label><input id="customer_name" name="customer_name" value="{{ old('customer_name', $sale->customer_name) }}" class="{{ $input }}"></div>
            <div><label class="mb-1 block text-sm font-semibold" for="sold_on">Date</label><input id="sold_on" type="date" name="sold_on" value="{{ old('sold_on', $sale->sold_on->toDateString()) }}" required class="{{ $input }}"></div>
        </section>

        @if ($locked)
            <p class="rounded-control bg-amber/30 p-3 text-sm font-semibold">এই ইনভয়েসে রিটার্ন বা চালান আছে, তাই আইটেম বদলানো যাবে না। শুধু তারিখ, কাস্টমার ও নোট বদলানো যায়।</p>
            <div class="overflow-x-auto rounded-panel border border-steel bg-white"><table class="w-full text-left text-sm"><thead class="bg-surface"><tr><th class="p-3">Item</th><th class="p-3 text-right">Qty</th><th class="p-3 text-right">Price</th><th class="p-3 text-right">Amount</th></tr></thead>
                <tbody>@foreach ($sale->items as $i)<tr class="border-t border-steel"><td class="p-3">{{ $i->stockItem->label() }}</td><td class="p-3 text-right">{{ $i->quantity }}</td><td class="p-3 text-right">{{ Money::format($i->unit_price) }}</td><td class="p-3 text-right">{{ Money::format($i->line_total) }}</td></tr>@endforeach</tbody></table></div>
        @else
            @include('admin.erp._lines', ['mode' => 'sale', 'stockItems' => $stockItems, 'oldItems' => collect(old('items') ? collect(old('items'))->map(fn ($r) => ['stock_item_id' => $r['stock_item_id'] ?? '', 'quantity' => $r['quantity'] ?? 1, 'price' => $r['unit_price'] ?? '']) : $rows)->values()->all(), 'discount' => old('discount', $sale->discount), 'tax' => old('tax_percent', $sale->tax_percent)])
        @endif

        <section class="rounded-panel border border-steel bg-white p-6"><label class="mb-1 block text-sm font-semibold" for="notes">Notes (printed on the invoice)</label><textarea id="notes" name="notes" rows="2" class="{{ $input }}">{{ old('notes', $sale->notes) }}</textarea></section>
        <div><x-button>Save changes</x-button> <a class="ml-3 underline text-industrial" href="{{ route('admin.sales.show', $sale) }}">Cancel</a></div>
    </form>
</x-layouts.admin-shell>
