@php
    $priceField = $mode === 'sale' ? 'unit_price' : 'unit_cost';
    $cfg = ['items' => $stockItems, 'rows' => $oldItems, 'mode' => $mode, 'priceField' => $priceField, 'discount' => $discount ?? 0, 'tax' => $tax ?? 0, 'symbol' => \App\Support\Money::symbol()];
    $input = 'rounded-control border border-steel px-2 py-2';
@endphp
<div x-data="erpLines(@js($cfg))" class="space-y-4">
    @if (empty($stockItems))
        <p role="alert" class="rounded-control bg-amber/30 p-3 text-sm font-semibold">কোনো স্টক আইটেম নেই। আগে <a class="underline" href="{{ route('admin.stock.index') }}">Stock</a> পাতায় গিয়ে "Add products to stock list" চাপুন।</p>
    @endif
    <div class="overflow-x-auto rounded-panel border border-steel bg-white">
        <table class="w-full text-left text-sm">
            <thead class="bg-surface"><tr><th class="p-3">Item</th><th class="p-3">Qty</th><th class="p-3">{{ $mode === 'sale' ? 'Price' : 'Cost' }}</th><th class="p-3 text-right">Line total</th><th class="p-3"><span class="sr-only">Remove</span></th></tr></thead>
            <tbody>
                <template x-for="(row, i) in rows" :key="i">
                    <tr class="border-t border-steel">
                        <td class="min-w-64 p-2">
                            <select :name="`items[${i}][stock_item_id]`" x-model="row.stock_item_id" x-on:change="pick(row)" required aria-label="Item" class="w-full {{ $input }}">
                                <option value="">Choose item…</option>
                                <template x-for="it in items" :key="it.id"><option :value="it.id" x-text="it.label + (mode === 'sale' ? '  (stock ' + it.stock + ')' : '')"></option></template>
                            </select>
                            <p x-show="mode === 'sale' && over(row)" x-cloak class="mt-1 text-xs font-semibold text-red-800">স্টকের চেয়ে বেশি।</p>
                        </td>
                        <td class="p-2"><input type="number" min="1" step="1" :name="`items[${i}][quantity]`" x-model="row.quantity" required aria-label="Quantity" class="w-24 {{ $input }}"></td>
                        <td class="p-2"><input type="number" min="0" step="0.01" :name="`items[${i}][{{ $priceField }}]`" x-model="row.price" required aria-label="Price" class="w-32 {{ $input }}"></td>
                        <td class="p-2 text-right font-semibold" x-text="money((Number(row.quantity) || 0) * (Number(row.price) || 0))"></td>
                        <td class="p-2 text-right"><button type="button" class="text-red-800 underline" x-on:click="remove(i)">Remove</button></td>
                    </tr>
                </template>
            </tbody>
        </table>
    </div>
    <button type="button" class="font-semibold text-industrial underline" x-on:click="add()">+ Add another item</button>

    <dl class="ml-auto max-w-sm space-y-2 rounded-panel border border-steel bg-white p-5 text-sm">
        <div class="flex justify-between"><dt>Subtotal</dt><dd class="font-semibold" x-text="money(subtotal)"></dd></div>
        <div class="flex items-center justify-between gap-3"><dt><label for="discount">Discount</label></dt><dd><input id="discount" name="discount" type="number" min="0" step="0.01" x-model="discount" class="w-32 text-right {{ $input }}"></dd></div>
        @if ($mode === 'sale')
            <div class="flex items-center justify-between gap-3"><dt><label for="tax_percent">VAT / tax %</label></dt><dd><input id="tax_percent" name="tax_percent" type="number" min="0" max="100" step="0.01" x-model="tax" class="w-32 text-right {{ $input }}"></dd></div>
            <div class="flex justify-between text-ink/70"><dt>Tax amount</dt><dd x-text="money(taxValue)"></dd></div>
        @endif
        <div class="flex justify-between border-t border-steel pt-2 text-lg font-extrabold text-navy"><dt>Total</dt><dd x-text="money(total)"></dd></div>
    </dl>
</div>
