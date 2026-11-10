@php
    use App\Support\Money;
    $isSale = $kind === 'sale';
    $input = 'rounded-control border border-steel px-3 py-2';
    $number = $doc->number();
@endphp
<x-layouts.admin-shell title="{{ $isSale ? 'Sale return' : 'Purchase return' }} for {{ $number }}">
    <p class="mb-4 max-w-4xl text-sm">{{ $isSale ? 'ক্রেতা কোন পণ্য ফেরত দিচ্ছে তার পরিমাণ লিখুন। ফেরত পণ্য স্টকে ফিরে আসবে এবং ক্রেতার বকেয়া কমবে।' : 'সাপ্লায়ারকে কোন পণ্য ফেরত পাঠাচ্ছেন তার পরিমাণ লিখুন। স্টক কমবে এবং সাপ্লায়ারের কাছে আপনার পাওনা কমবে।' }}</p>
    @error('returns')<p role="alert" class="mb-4 max-w-4xl rounded-control border border-red-300 bg-red-50 p-3 text-sm text-red-900">{{ $message }}</p>@enderror
    <form method="POST" action="{{ $action }}" class="max-w-4xl space-y-6">
        @csrf
        <div class="overflow-x-auto rounded-panel border border-steel bg-white"><table class="w-full text-left text-sm">
            <thead class="bg-surface"><tr><th class="p-3">Item</th><th class="p-3 text-right">{{ $isSale ? 'Sold' : 'Bought' }}</th><th class="p-3 text-right">Can still return</th><th class="p-3 text-right">{{ $isSale ? 'Price' : 'Cost' }}</th><th class="p-3">Return qty</th></tr></thead>
            <tbody>@foreach ($lines as $l)<tr class="border-t border-steel"><td class="p-3">{{ $l['label'] }}</td><td class="p-3 text-right">{{ $l['sold'] }}</td><td class="p-3 text-right">{{ $l['left'] }}</td><td class="p-3 text-right">{{ Money::format($l['price']) }}</td>
                <td class="p-3">@if ($l['left'] > 0)<input type="number" min="0" max="{{ $l['left'] }}" step="1" name="qty[{{ $l['id'] }}]" value="{{ old('qty.'.$l['id'], 0) }}" aria-label="Return quantity for {{ $l['label'] }}" class="w-24 {{ $input }}">@else<span class="text-xs">—</span>@endif</td></tr>@endforeach</tbody>
        </table></div>
        <div class="grid gap-4 rounded-panel border border-steel bg-white p-6 sm:grid-cols-2">
            <div><label class="mb-1 block text-sm font-semibold" for="returned_on">Date</label><input id="returned_on" type="date" name="returned_on" value="{{ old('returned_on', today()->toDateString()) }}" required class="w-full {{ $input }}"></div>
            <div><label class="mb-1 block text-sm font-semibold" for="reason">Reason</label><input id="reason" name="reason" maxlength="255" value="{{ old('reason') }}" placeholder="e.g. Damaged, wrong item" class="w-full {{ $input }}"></div>
        </div>
        <div><x-button>Save return</x-button> <a class="ml-3 underline text-industrial" href="{{ route($isSale ? 'admin.sales.show' : 'admin.purchases.show', $doc) }}">Cancel</a></div>
    </form>
</x-layouts.admin-shell>
