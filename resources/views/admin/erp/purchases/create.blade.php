@php $input = 'w-full rounded-control border border-steel px-3 py-2'; @endphp
<x-layouts.admin-shell title="New purchase">
    <form method="POST" action="{{ route('admin.purchases.store') }}" class="max-w-5xl space-y-6">
        @csrf
        <section class="grid gap-4 rounded-panel border border-steel bg-white p-6 sm:grid-cols-2">
            <div><label class="mb-1 block text-sm font-semibold" for="supplier_id">Supplier</label>
                <select id="supplier_id" name="supplier_id" required class="{{ $input }}"><option value="">Choose…</option>@foreach ($suppliers as $s)<option value="{{ $s->id }}" @selected(old('supplier_id') == $s->id)>{{ $s->name }}</option>@endforeach</select>
                <p class="mt-1 text-xs"><a class="underline text-industrial" href="{{ route('admin.parties.index', 'supplier') }}">নতুন সাপ্লায়ার যোগ করুন</a></p></div>
            <div><label class="mb-1 block text-sm font-semibold" for="purchased_on">Date</label><input id="purchased_on" type="date" name="purchased_on" value="{{ old('purchased_on', today()->toDateString()) }}" required class="{{ $input }}"></div>
        </section>

        @include('admin.erp._lines', ['mode' => 'purchase', 'stockItems' => $stockItems, 'oldItems' => collect(old('items', []))->map(fn ($r) => ['stock_item_id' => $r['stock_item_id'] ?? '', 'quantity' => $r['quantity'] ?? 1, 'price' => $r['unit_cost'] ?? ''])->values()->all(), 'discount' => old('discount', 0), 'tax' => 0])

        <section class="grid gap-4 rounded-panel border border-steel bg-white p-6 sm:grid-cols-3">
            <div><label class="mb-1 block text-sm font-semibold" for="paid">Paid now (optional)</label><input id="paid" type="number" step="0.01" min="0" name="paid" value="{{ old('paid') }}" class="{{ $input }}"></div>
            <div><label class="mb-1 block text-sm font-semibold" for="method">Payment method</label><select id="method" name="method" class="{{ $input }}">@foreach (\App\Models\Payment::METHODS as $k => $v)<option value="{{ $k }}">{{ $v }}</option>@endforeach</select></div>
            <div class="sm:col-span-3"><label class="mb-1 block text-sm font-semibold" for="notes">Notes</label><textarea id="notes" name="notes" rows="2" class="{{ $input }}">{{ old('notes') }}</textarea></div>
        </section>
        <div><x-button>Save purchase</x-button> <a class="ml-3 underline text-industrial" href="{{ route('admin.purchases.index') }}">Cancel</a></div>
    </form>
</x-layouts.admin-shell>
