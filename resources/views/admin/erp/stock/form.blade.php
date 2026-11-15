@php $input = 'w-full rounded-control border border-steel px-3 py-2'; $label = 'mb-1 block text-sm font-semibold'; @endphp
<x-layouts.admin-shell title="{{ $item->exists ? 'Edit stock item' : 'New stock item' }}">
    @error('delete')<p role="alert" class="mb-4 max-w-3xl rounded-control border border-red-300 bg-red-50 p-3 text-sm text-red-900">{{ $message }}</p>@enderror
    <form method="POST" action="{{ $item->exists ? route('admin.stock.save', $item) : route('admin.stock.store') }}" class="grid max-w-3xl gap-4 rounded-panel border border-steel bg-white p-6 sm:grid-cols-2">
        @csrf @if ($item->exists) @method('PUT') @endif
        <div class="sm:col-span-2">
            @if ($item->exists)
                <p class="{{ $label }}">Product</p><p class="font-bold text-navy">{{ $item->product?->name }} <span class="text-sm font-normal">({{ $item->product?->sku }})</span></p>
            @else
                <label class="{{ $label }}" for="product_id">Product</label>
                <select id="product_id" name="product_id" required class="{{ $input }}"><option value="">Choose…</option>@foreach ($products as $p)<option value="{{ $p->id }}" @selected(old('product_id') == $p->id)>{{ $p->name }} ({{ $p->sku }})</option>@endforeach</select>
            @endif
        </div>
        <div><label class="{{ $label }}" for="pack">Pack size</label><input id="pack" name="pack" maxlength="60" value="{{ old('pack', $item->pack) }}" placeholder="e.g. 4 L (leave empty if the product has no pack sizes)" class="{{ $input }}"></div>
        <div><label class="{{ $label }}" for="reorder_level">Reorder level</label><input id="reorder_level" type="number" min="0" name="reorder_level" value="{{ old('reorder_level', $item->reorder_level) }}" required class="{{ $input }}"></div>
        <div><label class="{{ $label }}" for="sale_price">Sale price</label><input id="sale_price" type="number" step="0.01" min="0" name="sale_price" value="{{ old('sale_price', $item->sale_price) }}" required class="{{ $input }}"></div>
        <div><label class="{{ $label }}" for="cost_price">Average cost</label><input id="cost_price" type="number" step="0.01" min="0" name="cost_price" value="{{ old('cost_price', $item->cost_price) }}" required class="{{ $input }}"><p class="mt-1 text-xs text-ink/70">নতুন ক্রয়ে এটা নিজে হিসাব হয়। প্রয়োজনে এখানে সংশোধন করা যায়।</p></div>
        <div class="sm:col-span-2"><x-button>Save</x-button> <a class="ml-3 underline text-industrial" href="{{ $item->exists ? route('admin.stock.show', $item) : route('admin.stock.index') }}">Cancel</a></div>
    </form>

    @if ($item->exists)
        <div class="mt-6 max-w-3xl rounded-panel border border-red-200 bg-white p-5">
            <h2 class="font-extrabold text-red-800">Delete this stock item</h2>
            <p class="mt-1 text-sm">শুধু তখনই মোছা যায় যখন এর কোনো স্টক নড়াচড়া বা ডকুমেন্ট নেই (ভুল করে বানানো আইটেম)। ব্যবহৃত আইটেম মোছা যায় না, কারণ পুরনো ইনভয়েসে এর নাম থাকে।</p>
            <form method="POST" action="{{ route('admin.stock.destroy', $item) }}" class="mt-3" x-on:submit="if (!confirm('Delete this stock item?')) $event.preventDefault()">@csrf @method('DELETE')<button class="rounded-control border border-red-300 px-4 py-2 font-semibold text-red-800 hover:bg-red-50">Delete stock item</button></form>
        </div>
    @endif
</x-layouts.admin-shell>
