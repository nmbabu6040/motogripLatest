@php $input = 'w-full rounded-control border border-steel px-3 py-2'; @endphp
<x-layouts.admin-shell title="New sale">
    <form method="POST" action="{{ route('admin.sales.store') }}" class="max-w-5xl space-y-6">
        @csrf
        <section class="grid gap-4 rounded-panel border border-steel bg-white p-6 sm:grid-cols-3">
            <div><label class="mb-1 block text-sm font-semibold" for="customer_id">Customer</label>
                <select id="customer_id" name="customer_id" class="{{ $input }}"><option value="">Walk-in customer</option>@foreach ($customers as $c)<option value="{{ $c->id }}" @selected(old('customer_id') == $c->id)>{{ $c->name }}</option>@endforeach</select>
                <p class="mt-1 text-xs"><a class="underline text-industrial" href="{{ route('admin.parties.index', 'customer') }}">নতুন কাস্টমার যোগ করুন</a></p></div>
            <div><label class="mb-1 block text-sm font-semibold" for="customer_name">Walk-in name (optional)</label><input id="customer_name" name="customer_name" value="{{ old('customer_name') }}" class="{{ $input }}"></div>
            <div><label class="mb-1 block text-sm font-semibold" for="sold_on">Date</label><input id="sold_on" type="date" name="sold_on" value="{{ old('sold_on', today()->toDateString()) }}" required class="{{ $input }}"></div>
        </section>

        @include('admin.erp._lines', ['mode' => 'sale', 'stockItems' => $stockItems, 'oldItems' => collect(old('items', []))->map(fn ($r) => ['stock_item_id' => $r['stock_item_id'] ?? '', 'quantity' => $r['quantity'] ?? 1, 'price' => $r['unit_price'] ?? ''])->values()->all(), 'discount' => old('discount', 0), 'tax' => old('tax_percent', $defaultTax)])

        <section class="grid gap-4 rounded-panel border border-steel bg-white p-6 sm:grid-cols-3">
            <div><label class="mb-1 block text-sm font-semibold" for="paid">Paid now (optional)</label><input id="paid" type="number" step="0.01" min="0" name="paid" value="{{ old('paid') }}" class="{{ $input }}"></div>
            <div><label class="mb-1 block text-sm font-semibold" for="method">Payment method</label><select id="method" name="method" class="{{ $input }}">@foreach (\App\Models\Payment::METHODS as $k => $v)<option value="{{ $k }}">{{ $v }}</option>@endforeach</select></div>
            <div class="sm:col-span-3"><label class="mb-1 block text-sm font-semibold" for="notes">Notes (printed on the invoice)</label><textarea id="notes" name="notes" rows="2" class="{{ $input }}">{{ old('notes') }}</textarea></div>
        </section>
        <div><x-button>Create invoice</x-button> <a class="ml-3 underline text-industrial" href="{{ route('admin.sales.index') }}">Cancel</a></div>
    </form>
</x-layouts.admin-shell>
